<?php

declare(strict_types=1);

use App\Actions\Game\NotifyGameActivityAction;
use App\Enums\GameActivity;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\RankingConfiguration;
use App\Models\Team;
use App\Models\User;
use App\Notifications\GameActivityNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    foreach (['view-games', 'moderate-games', 'override-moderation'] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission]);
    }

    Queue::fake();
    RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);
});

/**
 * @param  array<string, mixed>  $gameAttributes
 * @return array{Game, User, User}
 */
function gameWithSubmittedResult(array $gameAttributes = [], bool $videoReady = true): array
{
    $creator = User::factory()->create(['name' => 'Alice'])->givePermissionTo('view-games');
    $opponent = User::factory()->create(['name' => 'Bob'])->givePermissionTo('view-games');
    $factory = Game::factory()->againstPlayer($opponent)->awaitingConfirmation();
    $game = ($videoReady ? $factory->withReadyVideo() : $factory)->create(['player_id' => $creator->id, 'title' => 'Derby', ...$gameAttributes]);
    GameResult::factory()->create(['game_id' => $game->id, 'submitter_id' => $creator->id, 'your_score' => 21, 'opponent_score' => 15]);

    return [$game, $creator, $opponent];
}

function sentActivity(User $user, GameActivity $activity): ?GameActivityNotification
{
    foreach (Notification::sent($user, GameActivityNotification::class) as $notification) {
        if ($notification instanceof GameActivityNotification && $notification->activity === $activity) {
            return $notification;
        }
    }

    return null;
}

test('confirming notifies the submitter', function (): void {
    Notification::fake();
    [$game, $creator, $opponent] = gameWithSubmittedResult();

    $this->actingAs($opponent)->post(route('admin.games.result.confirm', $game));

    expect(sentActivity($creator, GameActivity::ResultConfirmed))->not->toBeNull()
        ->and(sentActivity($opponent, GameActivity::ResultConfirmed))->toBeNull();
});

test('rejecting notifies the submitter with the reason', function (): void {
    Notification::fake();
    [$game, $creator, $opponent] = gameWithSubmittedResult();

    $this->actingAs($opponent)->post(route('admin.games.result.reject', $game), ['reason' => 'It was 19-21.']);

    $notification = sentActivity($creator, GameActivity::ResultRejected);
    expect($notification)->not->toBeNull()
        ->and($notification?->toArray($creator)['message'])->toContain('Their reason: "It was 19-21."');
});

test('only the submitting side is asked to upload a missing video', function (): void {
    Notification::fake();
    [$game, $creator, $opponent] = gameWithSubmittedResult(videoReady: false);

    $this->actingAs($opponent)->post(route('admin.games.result.confirm', $game));

    $notification = sentActivity($creator, GameActivity::ResultConfirmed);
    expect($notification?->toArray($creator)['message'])->toContain('Upload a video of the game')
        ->and($notification?->toArray($opponent)['message'])->not->toContain('Upload a video');
});

test('a moderation decision notifies both sides', function (string $decision, GameActivity $activity): void {
    Notification::fake();
    [$game, $creator, $opponent] = gameWithSubmittedResult(['status' => GameStatus::Pending]);
    $moderator = User::factory()->create()->givePermissionTo('moderate-games');

    $this->actingAs($moderator)->patch(route('admin.moderation.update', $game), ['status' => $decision, 'reason' => 'Reviewed.']);

    expect(sentActivity($creator, $activity))->not->toBeNull()
        ->and(sentActivity($opponent, $activity))->not->toBeNull();
})->with([
    'approved' => ['approved', GameActivity::Approved],
    'rejected' => ['rejected', GameActivity::Rejected],
    'flagged' => ['flagged', GameActivity::Flagged],
]);

test('an override decision notifies both sides', function (): void {
    Notification::fake();
    [$game, $creator, $opponent] = gameWithSubmittedResult(['status' => GameStatus::Flagged]);
    $admin = User::factory()->create()->givePermissionTo('override-moderation');

    $this->actingAs($admin)->patch(route('admin.override.update', $game), ['status' => 'approved', 'reason' => 'Overturned.']);

    expect(sentActivity($creator, GameActivity::Approved))->not->toBeNull()
        ->and(sentActivity($opponent, GameActivity::Approved))->not->toBeNull();
});

test('team results go to the opposing team owner', function (): void {
    Notification::fake();
    $owner = User::factory()->create();
    $opponentTeam = Team::factory()->create(['user_id' => $owner->id]);
    $game = Game::factory()->betweenTeams(Team::factory()->create(), $opponentTeam)->awaitingConfirmation()->create();
    GameResult::factory()->create(['game_id' => $game->id, 'submitter_id' => $game->player_id]);

    resolve(NotifyGameActivityAction::class)->handle($game, GameActivity::ConfirmationReminder);

    expect(sentActivity($owner, GameActivity::ConfirmationReminder))->not->toBeNull();
});

test('a person on both sides is only notified once', function (): void {
    Notification::fake();
    $player = User::factory()->create();
    $game = Game::factory()->create(['player_id' => $player->id, 'opponent_player_id' => $player->id]);
    GameResult::factory()->create(['game_id' => $game->id, 'submitter_id' => $player->id]);

    resolve(NotifyGameActivityAction::class)->handle($game, GameActivity::Approved);

    Notification::assertSentToTimes($player, GameActivityNotification::class, 1);
});

test('every activity renders a title, message and link', function (GameActivity $activity, string $title, string $message): void {
    [$game, $creator] = gameWithSubmittedResult(['status' => GameStatus::Contested]);
    $game->gameResult?->update(['rejection_reason' => 'Wrong score.']);
    $notification = new GameActivityNotification($game->refresh(), $activity);

    $data = $notification->toArray($creator);
    $mail = $notification->toMail($creator);

    expect($data['activity'])->toBe($activity->value)
        ->and($data['title'])->toBe($title)
        ->and($data['message'])->toContain($message)
        ->and($data['url'])->toBe(route('admin.games.show', $game))
        ->and($mail->subject)->toBe($title)
        ->and($mail->actionUrl)->toBe($data['url']);
})->with([
    [GameActivity::ResultSubmitted, 'Confirm the result of Derby', 'Alice submitted a final score of 21 – 15'],
    [GameActivity::ConfirmationReminder, 'Reminder: confirm the result of Derby', 'is still waiting for you to confirm'],
    [GameActivity::ResultConfirmed, 'Your result for Derby was confirmed', 'Bob confirmed the score of 21 – 15'],
    [GameActivity::ResultRejected, 'Your result for Derby was rejected', 'Their reason: "Wrong score."'],
    [GameActivity::ConfirmationExpired, 'Derby has gone to a moderator', 'Bob did not confirm the score'],
    [GameActivity::Approved, 'Derby was approved', 'now counts towards rankings'],
    [GameActivity::Rejected, 'Derby was rejected by a moderator', 'will not count towards rankings'],
    [GameActivity::Flagged, 'Derby was flagged by a moderator', 'you can dispute the decision'],
]);

test('only moderation outcomes map to an activity', function (GameStatus $status, ?GameActivity $activity): void {
    expect(GameActivity::forModeration($status))->toBe($activity);
})->with([
    [GameStatus::Approved, GameActivity::Approved],
    [GameStatus::Rejected, GameActivity::Rejected],
    [GameStatus::Flagged, GameActivity::Flagged],
    [GameStatus::Pending, null],
]);

test('notifications are stored and shared with every page', function (): void {
    [$game, $creator, $opponent] = gameWithSubmittedResult();
    $this->actingAs($opponent)->post(route('admin.games.result.confirm', $game))
        ->assertSessionHas('success');

    $this->actingAs($creator)
        ->get(route('admin.games.show', $game))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('notifications.unread_count', 1)
            ->where('notifications.recent.0.title', 'Your result for Derby was confirmed')
            ->where('notifications.recent.0.url', route('admin.games.show', $game))
            ->where('notifications.recent.0.read_at', null)
            ->where('flash.success', 'Result confirmed. It has been sent for moderation.')
        );
});

test('guests get no notifications', function (): void {
    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('notifications', null));
});

test('reading a notification marks it read and opens the game', function (): void {
    [$game, $creator] = gameWithSubmittedResult();
    resolve(NotifyGameActivityAction::class)->handle($game, GameActivity::ResultConfirmed);
    $notification = $creator->notifications()->sole();

    $this->actingAs($creator)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('admin.games.show', $game));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('a notification without a link sends the user back', function (): void {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => GameActivityNotification::class,
        'data' => ['title' => 'Legacy'],
    ]);

    $this->actingAs($user)->from(route('dashboard'))
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('dashboard'));
});

test("users cannot read someone else's notification", function (): void {
    [$game, $creator] = gameWithSubmittedResult();
    resolve(NotifyGameActivityAction::class)->handle($game, GameActivity::ResultConfirmed);
    $notification = $creator->notifications()->sole();

    $this->actingAs(User::factory()->create())
        ->post(route('notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->refresh()->read_at)->toBeNull();
});

test('all notifications can be marked read', function (): void {
    [$game, $creator] = gameWithSubmittedResult();
    resolve(NotifyGameActivityAction::class)->handle($game, GameActivity::ResultConfirmed);
    resolve(NotifyGameActivityAction::class)->handle($game, GameActivity::ResultRejected);

    $this->actingAs($creator)->post(route('notifications.read-all'))->assertRedirect();

    expect($creator->unreadNotifications()->count())->toBe(0);
});

test('the game page tells the opponent their confirmation is due and when', function (): void {
    $this->travelTo('2026-10-13 10:00:00');
    [$game, , $opponent] = gameWithSubmittedResult();

    $this->actingAs($opponent)
        ->get(route('admin.games.show', $game))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('canRespondToResult', true)
            ->where('canUploadVideo', false)
            ->where('confirmationDeadline', '2026-10-18T10:00:00.000000Z')
        );
});
