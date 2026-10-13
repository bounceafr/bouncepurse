<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Vimeo\Laravel\Facades\Vimeo;

beforeEach(function (): void {
    foreach (['view-games', 'create-games', 'edit-games', 'delete-games'] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission]);
    }
});

test('guests are redirected from games index', function (): void {
    $response = $this->get(route('admin.games.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view games index', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $this->actingAs($user);

    $response = $this->get(route('admin.games.index'));
    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/games/index')
        ->has('opponentPlayers')
        ->has('opponentTeams')
    );
});

test('games index can be filtered by search term', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $this->actingAs($user);

    $matching = Game::factory()->create(['title' => 'Championship Finals', 'player_id' => $user->id]);
    Game::factory()->create(['title' => 'Friendly Match', 'player_id' => $user->id]);

    $response = $this->get(route('admin.games.index', ['search' => 'Championship']));

    $response->assertOk();
    $response->assertInertia(
        fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/games/index')
            ->where('filters.search', 'Championship')
            ->has('games.data', 1)
            ->where('games.data.0.id', $matching->id)
            ->where('games.data.0.can.update', true)
    );
});

test('players see games where they are the opponent', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $opponentGame = Game::factory()->againstPlayer($player)->create();
    Game::factory()->againstPlayer()->create();
    $this->actingAs($player);

    $this->get(route('admin.games.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('games.data', 1)
            ->where('games.data.0.id', $opponentGame->id)
            ->where('games.data.0.can.update', false)
        );
});

test('authenticated users can create a game', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $opponent = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Test Game',
        'participant' => 'player',
        'format' => '1v1',
        'opponent_player_id' => $opponent->id,
        'court_id' => null,
        'played_at' => '2026-01-15 10:00:00',
    ]);

    $response->assertRedirect(route('admin.games.index'));

    $this->assertDatabaseHas('games', [
        'title' => 'Test Game',
        'format' => '1v1',
        'player_id' => $user->id,
        'opponent_player_id' => $opponent->id,
    ]);
});

test('create game validates required fields', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), []);

    $response->assertInvalid(['title', 'participant', 'format', 'played_at']);
});

test('creator can update their scheduled game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create(['player_id' => $user->id]);
    $newOpponent = User::factory()->create();
    $this->actingAs($user);

    $response = $this->patch(route('admin.games.update', $game), [
        'title' => 'Updated Game',
        'format' => '1v1',
        'court_id' => null,
        'played_at' => '2026-02-01 14:00:00',
        'opponent_player_id' => $newOpponent->id,
        'points' => 42,
        'comments' => 'Great game!',
    ]);

    $response->assertRedirect(route('admin.games.index'));

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'title' => 'Updated Game',
        'opponent_player_id' => $newOpponent->id,
        'points' => 42,
        'comments' => 'Great game!',
    ]);
});

test('a player game cannot be updated to play against its creator', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Mirror',
        'format' => '1v1',
        'played_at' => '2026-02-01 14:00:00',
        'opponent_player_id' => $user->id,
    ])->assertSessionHasErrors('opponent_player_id');
});

test('team game opponents are validated on update', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $team = Team::factory()->create(['user_id' => $user->id]);
    $game = Game::factory()->scheduled()->betweenTeams($team, Team::factory()->create())->create(['player_id' => $user->id]);
    $newOpponent = Team::factory()->create();
    $this->actingAs($user);

    $payload = ['title' => 'Team Game', 'format' => '5v5', 'played_at' => '2026-02-01 14:00:00'];

    $this->patch(route('admin.games.update', $game), [...$payload, 'opponent_team_id' => $team->id])
        ->assertSessionHasErrors('opponent_team_id');
    $this->patch(route('admin.games.update', $game), [...$payload, 'opponent_player_id' => $user->id])
        ->assertSessionHasErrors('opponent_player_id');
    $this->patch(route('admin.games.update', $game), [...$payload, 'opponent_team_id' => $newOpponent->id])
        ->assertRedirect(route('admin.games.index'));

    expect($game->refresh()->opponent_team_id)->toBe($newOpponent->id);
});

test('client supplied result is ignored when updating a game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Updated Game',
        'format' => '1v1',
        'played_at' => '2026-02-01 14:00:00',
        'result' => 'win',
    ]);

    expect($game->refresh()->result)->toBeNull();
});

test('users cannot update another player game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create();
    $this->actingAs($user);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Hijacked',
        'format' => '1v1',
        'played_at' => '2026-02-01 14:00:00',
    ])->assertForbidden();
});

test('users with edit-games permission can update any scheduled game', function (): void {
    $admin = User::factory()->create()->givePermissionTo(['view-games', 'edit-games']);
    $game = Game::factory()->scheduled()->againstPlayer()->create();
    $this->actingAs($admin);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Admin Edit',
        'format' => '1v1',
        'played_at' => '2026-02-01 14:00:00',
    ])->assertRedirect(route('admin.games.index'));

    expect($game->refresh()->title)->toBe('Admin Edit');
});

test('a game cannot be edited once its result has been submitted', function (GameStatus $status): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->againstPlayer()->create(['player_id' => $user->id, 'status' => $status]);
    GameResult::factory()->create(['game_id' => $game->id, 'submitter_id' => $user->id]);
    $this->actingAs($user);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Rewritten History',
        'format' => '1v1',
        'played_at' => '2026-02-01 14:00:00',
    ])->assertSessionHasErrors('game');

    expect($game->refresh()->title)->not->toBe('Rewritten History');
})->with([
    'awaiting confirmation' => GameStatus::AwaitingConfirmation,
    'pending moderation' => GameStatus::Pending,
    'approved' => GameStatus::Approved,
]);

test('creator can delete their game before a result is submitted', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->delete(route('admin.games.destroy', $game))->assertRedirect();

    $this->assertDatabaseMissing('games', ['id' => $game->id]);
});

test('users cannot delete another player game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create();
    $this->actingAs($user);

    $this->delete(route('admin.games.destroy', $game))->assertForbidden();

    $this->assertDatabaseHas('games', ['id' => $game->id]);
});

test('a game with a submitted result cannot be deleted', function (): void {
    $admin = User::factory()->create()->givePermissionTo(['view-games', 'delete-games']);
    $game = Game::factory()->create(['status' => GameStatus::Approved]);
    GameResult::factory()->create(['game_id' => $game->id]);
    $this->actingAs($admin);

    $this->delete(route('admin.games.destroy', $game))->assertSessionHasErrors('game');

    $this->assertDatabaseHas('games', ['id' => $game->id]);
});

test('initiate upload returns json with upload link', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['title' => 'My Game', 'player_id' => $user->id]);
    $this->actingAs($user);

    Vimeo::shouldReceive('request')
        ->once()
        ->with('/me/videos', [
            'upload' => [
                'approach' => 'tus',
                'size' => 1024000,
            ],
            'name' => 'My Game',
        ], 'POST')
        ->andReturn([
            'body' => [
                'uri' => '/videos/123456789',
                'upload' => [
                    'upload_link' => 'https://asia-files.tus.vimeo.com/files/123456789',
                ],
            ],
        ]);

    $response = $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ]);

    $response->assertOk();
    $response->assertJson(['upload_link' => 'https://asia-files.tus.vimeo.com/files/123456789']);

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'vimeo_uri' => '/videos/123456789',
        'vimeo_status' => 'pending',
    ]);
});

test('users cannot start an upload for another player game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create();
    $this->actingAs($user);

    Vimeo::shouldReceive('request')->never();

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ])->assertForbidden();
});

test('complete upload sets vimeo status to complete', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create([
        'player_id' => $user->id,
        'vimeo_uri' => '/videos/123456789',
        'vimeo_status' => 'pending',
    ]);
    $this->actingAs($user);

    $response = $this->patch(route('admin.games.complete-upload', $game));

    $response->assertRedirect(route('admin.games.index'));

    $this->assertDatabaseHas('games', [
        'id' => $game->id,
        'vimeo_status' => 'complete',
    ]);
});

test('users cannot complete an upload for another player game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['vimeo_status' => 'pending']);
    $this->actingAs($user);

    $this->patch(route('admin.games.complete-upload', $game))->assertForbidden();

    expect($game->refresh()->vimeo_status)->toBe('pending');
});

test('authenticated users can view the upload game page', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->get(route('admin.games.upload', $game));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/games/upload')
        ->where('game.id', $game->id)
        ->has('canSubmitResult')
    );
});
