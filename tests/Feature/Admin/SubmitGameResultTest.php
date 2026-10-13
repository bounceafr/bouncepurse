<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\ResultStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\Team;
use App\Models\User;
use App\Notifications\GameResultAwaitingConfirmationNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'view-games']);
    Notification::fake();
});

/**
 * @param  array<string, int|string>  $overrides
 * @return array<string, int|string>
 */
function resultPayload(array $overrides = []): array
{
    return array_merge([
        'your_score' => 21,
        'opponent_score' => 15,
        'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'finished_at' => now()->subHour()->format('Y-m-d H:i:s'),
    ], $overrides);
}

function playedGameFor(User $user, ?User $opponent = null): Game
{
    return Game::factory()->scheduled()->againstPlayer($opponent)->create([
        'player_id' => $user->id,
        'scheduled_at' => now()->subDay(),
    ]);
}

test('player can submit result for their own game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $response = $this->post(route('admin.games.result.store', $game), resultPayload());

    $response->assertRedirect(route('admin.games.show', $game));
    $this->assertDatabaseHas('game_results', [
        'game_id' => $game->id,
        'submitter_id' => $user->id,
        'your_score' => 21,
        'opponent_score' => 15,
        'confirmed_at' => null,
    ]);
});

test('cannot submit result for another player game', function (): void {
    $owner = User::factory()->create()->givePermissionTo('view-games');
    $other = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($owner);
    $this->actingAs($other);

    $this->post(route('admin.games.result.store', $game), resultPayload())->assertForbidden();
});

test('the opponent cannot submit the result', function (): void {
    $owner = User::factory()->create()->givePermissionTo('view-games');
    $opponent = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($owner, $opponent);
    $this->actingAs($opponent);

    $this->post(route('admin.games.result.store', $game), resultPayload())->assertForbidden();
});

test('a teammate can submit the result for their team game', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $creator = User::factory()->create()->assignRole(Role::Player->value);
    $teammate = User::factory()->create()->assignRole(Role::Player->value);
    $team = Team::factory()->create(['user_id' => $creator->id]);
    $team->members()->attach([$creator->id, $teammate->id], ['joined_at' => now()]);
    $game = Game::factory()->scheduled()->betweenTeams($team, Team::factory()->create())->create([
        'player_id' => $creator->id,
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($teammate);

    $this->post(route('admin.games.result.store', $game), resultPayload())
        ->assertRedirect(route('admin.games.show', $game));

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingConfirmation);
});

test('submitting result updates played_at on game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload());

    expect($game->refresh()->played_at)->not->toBeNull();
});

test('submitting result waits for opponent confirmation', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload());

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingConfirmation);
});

test('submitting result notifies the opponent', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $opponent = User::factory()->create();
    $game = playedGameFor($user, $opponent);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload());

    Notification::assertSentTo($opponent, GameResultAwaitingConfirmationNotification::class);
});

test('submitting a team result notifies the opposing team owner', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $team = Team::factory()->create(['user_id' => $user->id]);
    $team->members()->attach($user->id, ['joined_at' => now()]);
    $opponentOwner = User::factory()->create();
    $opponentTeam = Team::factory()->create(['user_id' => $opponentOwner->id]);
    $game = Game::factory()->scheduled()->betweenTeams($team, $opponentTeam)->create([
        'player_id' => $user->id,
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload());

    Notification::assertSentTo($opponentOwner, GameResultAwaitingConfirmationNotification::class);
});

test('game outcome is derived from submitted scores', function (int $yourScore, int $opponentScore, ResultStatus $expected): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload([
        'your_score' => $yourScore,
        'opponent_score' => $opponentScore,
    ]));

    expect($game->refresh()->result)->toBe($expected);
})->with([
    'win' => [21, 15, ResultStatus::WIN],
    'loss' => [12, 21, ResultStatus::LOST],
]);

test('a game cannot end in a tie', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload([
        'your_score' => 20,
        'opponent_score' => 20,
    ]))->assertSessionHasErrors('opponent_score');
});

test('submitting twice does not duplicate the result', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload());
    $this->post(route('admin.games.result.store', $game), resultPayload(['your_score' => 30]))
        ->assertSessionHasErrors('result');

    expect(GameResult::query()->where('game_id', $game->id)->count())->toBe(1)
        ->and($game->gameResult()->sole()->your_score)->toBe(21);
});

test('a reviewed game cannot be reopened by resubmitting', function (GameStatus $status): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->againstPlayer()->create(['player_id' => $user->id, 'status' => $status]);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload())
        ->assertSessionHasErrors('result');

    expect($game->refresh()->status)->toBe($status);
})->with([
    'approved' => GameStatus::Approved,
    'rejected' => GameStatus::Rejected,
    'flagged' => GameStatus::Flagged,
    'pending' => GameStatus::Pending,
]);

test('cannot submit a result before the scheduled time', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create([
        'player_id' => $user->id,
        'scheduled_at' => now()->addDay(),
    ]);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload())
        ->assertSessionHasErrors('result');

    $this->assertDatabaseMissing('game_results', ['game_id' => $game->id]);
});

test('cannot submit a result for a game without an opponent', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->create([
        'player_id' => $user->id,
        'scheduled_at' => now()->subDay(),
    ]);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload())
        ->assertSessionHasErrors('result');
});

test('show page reports submission eligibility from the server', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $future = Game::factory()->scheduled()->againstPlayer()->create([
        'player_id' => $user->id,
        'scheduled_at' => now()->addDay(),
    ]);
    $this->actingAs($user);

    $this->get(route('admin.games.show', $future))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('canSubmitResult', false)
            ->where('submitResultBlockedReason', 'You can submit results after the scheduled time.')
            ->where('canRespondToResult', false)
        );

    $this->get(route('admin.games.show', playedGameFor($user)))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('canSubmitResult', true)
            ->where('submitResultBlockedReason', null)
        );
});

test('cannot submit with finished_at before started_at', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload([
        'started_at' => now()->format('Y-m-d H:i:s'),
        'finished_at' => now()->subHour()->format('Y-m-d H:i:s'),
    ]))->assertSessionHasErrors('finished_at');
});

test('cannot submit result with future started_at', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload([
        'started_at' => now()->addHour()->format('Y-m-d H:i:s'),
        'finished_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
    ]))->assertSessionHasErrors('started_at');
});

test('cannot submit result with future finished_at', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload([
        'started_at' => now()->subHour()->format('Y-m-d H:i:s'),
        'finished_at' => now()->addHour()->format('Y-m-d H:i:s'),
    ]))->assertSessionHasErrors('finished_at');
});

test('cannot submit negative scores', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload([
        'your_score' => -1,
        'opponent_score' => -5,
    ]))->assertSessionHasErrors(['your_score', 'opponent_score']);
});

test('a concurrent duplicate submission is rejected by the unique constraint', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = playedGameFor($user);
    $this->actingAs($user);

    // Simulate another request inserting a result after this one passed its eligibility check.
    GameResult::creating(function (GameResult $result) use ($user): void {
        DB::table('game_results')->insert([
            'uuid' => Str::uuid(),
            'game_id' => $result->game_id,
            'submitter_id' => $user->id,
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHour(),
            'your_score' => 1,
            'opponent_score' => 2,
        ]);
    });

    $this->post(route('admin.games.result.store', $game), resultPayload())
        ->assertSessionHasErrors('result');

    // The whole submission rolled back: the game is untouched and no one is asked to confirm.
    expect($game->refresh()->status)->toBe(GameStatus::Scheduled);
    Notification::assertNothingSent();
});

test('the confirmation email shows the submitted score and links to the game', function (): void {
    $user = User::factory()->create(['name' => 'Alice'])->givePermissionTo('view-games');
    $opponent = User::factory()->create();
    $game = playedGameFor($user, $opponent);
    $this->actingAs($user);

    $this->post(route('admin.games.result.store', $game), resultPayload());

    /** @var GameResultAwaitingConfirmationNotification $notification */
    $notification = Notification::sent($opponent, GameResultAwaitingConfirmationNotification::class)->sole();
    $mail = $notification->toMail($opponent);

    expect($notification->via($opponent))->toBe(['mail'])
        ->and($mail->subject)->toContain($game->title)
        ->and($mail->introLines[0])->toContain('Alice submitted a final score of 21 – 15')
        ->and($mail->actionUrl)->toBe(route('admin.games.show', $game));
});
