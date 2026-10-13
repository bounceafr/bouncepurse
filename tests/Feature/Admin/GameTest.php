<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Enums\VideoStatus;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Vimeo\Laravel\Facades\Vimeo;

beforeEach(function (): void {
    foreach (['view-games', 'create-games', 'edit-games', 'delete-games'] as $permission) {
        Permission::query()->firstOrCreate(['name' => $permission]);
    }

    Storage::fake('videos');
    config(['games.videos.reserved_disk_space' => 0]);
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

    $matching = Game::factory()->scheduled()->create(['title' => 'Championship Finals', 'player_id' => $user->id]);
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

test('row actions reflect whether the game is still open', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $reviewed = Game::factory()->create(['player_id' => $user->id, 'status' => GameStatus::Approved]);
    GameResult::factory()->create(['game_id' => $reviewed->id]);
    $this->actingAs($user);

    $this->get(route('admin.games.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('games.data.0.can.update', false)
            ->where('games.data.0.can.delete', false)
            ->where('games.data.0.can.upload_video', false)
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
        'played_at' => '2026-01-15T10:00:00+00:00',
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
        'played_at' => '2026-02-01T14:00:00+00:00',
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

test('a UTC+10 player can record a game played today in their local date', function (): void {
    // 21:00 UTC on the 13th is already 07:00 on the 14th in UTC+10.
    $this->travelTo('2026-10-13 21:00:00');
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Morning Game',
        'participant' => 'player',
        'format' => '1v1',
        'opponent_player_id' => User::factory()->create()->id,
        'played_at' => '2026-10-14T00:00:00+10:00',
    ])->assertSessionHasNoErrors();

    expect(Game::query()->where('title', 'Morning Game')->sole()->played_at?->toDateTimeString())
        ->toBe('2026-10-13 14:00:00');
});

test('updating played_at normalises it to UTC', function (): void {
    $this->travelTo('2026-10-13 21:00:00');
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->patch(route('admin.games.update', $game), [
        'title' => $game->title,
        'format' => '1v1',
        'played_at' => '2026-10-14T00:00:00+10:00',
    ])->assertSessionHasNoErrors();

    expect($game->refresh()->played_at?->toDateTimeString())->toBe('2026-10-13 14:00:00');
});

test('played_at without a timezone offset is rejected', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $game = Game::factory()->scheduled()->againstPlayer()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Ambiguous',
        'participant' => 'player',
        'format' => '1v1',
        'opponent_player_id' => User::factory()->create()->id,
        'played_at' => '2026-02-01',
    ])->assertSessionHasErrors(['played_at' => 'The played date must include a timezone offset.']);

    $this->patch(route('admin.games.update', $game), [
        'title' => $game->title,
        'format' => '1v1',
        'played_at' => '2026-02-01',
    ])->assertSessionHasErrors(['played_at' => 'The played date must include a timezone offset.']);
});

test('a player game cannot be updated to play against its creator', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Mirror',
        'format' => '1v1',
        'played_at' => '2026-02-01T14:00:00+00:00',
        'opponent_player_id' => $user->id,
    ])->assertSessionHasErrors('opponent_player_id');
});

test('team game opponents are validated on update', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $team = Team::factory()->create(['user_id' => $user->id]);
    $game = Game::factory()->scheduled()->betweenTeams($team, Team::factory()->create())->create(['player_id' => $user->id]);
    $newOpponent = Team::factory()->create();
    $this->actingAs($user);

    $payload = ['title' => 'Team Game', 'format' => '5v5', 'played_at' => '2026-02-01T14:00:00+00:00'];

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
        'played_at' => '2026-02-01T14:00:00+00:00',
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
        'played_at' => '2026-02-01T14:00:00+00:00',
    ])->assertForbidden();
});

test('users with edit-games permission can update any scheduled game', function (): void {
    $admin = User::factory()->create()->givePermissionTo(['view-games', 'edit-games']);
    $game = Game::factory()->scheduled()->againstPlayer()->create();
    $this->actingAs($admin);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Admin Edit',
        'format' => '1v1',
        'played_at' => '2026-02-01T14:00:00+00:00',
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
        'played_at' => '2026-02-01T14:00:00+00:00',
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

test('deleting a game removes the videos this server stored for it', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['player_id' => $user->id]);
    Storage::disk('videos')->put($game->uuid.'/video.mp4', 'video');
    Storage::disk('videos')->put('other-game/video.mp4', 'video');
    $this->actingAs($user);

    $this->delete(route('admin.games.destroy', $game))->assertRedirect();

    Storage::disk('videos')->assertMissing($game->uuid.'/video.mp4');
    Storage::disk('videos')->assertExists('other-game/video.mp4');
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

test('initiate upload starts a resumable upload on this server', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['title' => 'My Game', 'player_id' => $user->id]);
    $this->actingAs($user);

    Vimeo::shouldReceive('request')->never();

    $response = $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.MP4',
    ]);

    $response->assertOk()->assertJson(['storage' => 'server']);

    /** @var string $uploadId */
    $uploadId = $response->json('upload_id');

    expect($uploadId)->toMatch('/^[0-9a-z]{26}$/')
        ->and($response->json('upload_link'))->toBe(route('admin.games.video-upload.offset', [$game, $uploadId]));

    expect($game->refresh())
        ->video_path->toBe($game->uuid.'/'.$uploadId.'.mp4')
        ->video_size->toBe(1024000)
        ->vimeo_uri->toBeNull()
        ->vimeo_status->toBe(VideoStatus::Uploading);

    Storage::disk('videos')->assertExists($game->uuid.'/'.$uploadId.'.mp4');
});

test('a server upload keeps only a plain file extension', function (string $fileName, string $suffix): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => $fileName,
    ])->assertOk();

    expect($game->refresh()->video_path)->toMatch('#^'.$game->uuid.'/[0-9a-z]{26}'.preg_quote($suffix, '#').'$#');
})->with([
    'video extension' => ['clip.mov', '.mov'],
    'no extension' => ['clip', ''],
    'odd extension' => ['clip.m p4', ''],
]);

test('initiate upload fails over to Vimeo when the upload to this server failed', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['title' => 'My Game', 'player_id' => $user->id]);
    $this->actingAs($user);

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ])->assertOk();
    $serverPath = (string) $game->refresh()->video_path;

    expectVimeoUploadCreated('My Game');
    Log::shouldReceive('warning')->once()->with(sprintf('Uploading the video for game %d to Vimeo because the upload to this server failed.', $game->id));

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
        'failover' => true,
    ])->assertOk()->assertExactJson([
        'storage' => 'vimeo',
        'upload_link' => 'https://asia-files.tus.vimeo.com/files/123456789',
        'upload_id' => '/videos/123456789',
    ]);

    expect($game->refresh())
        ->vimeo_uri->toBe('/videos/123456789')
        ->video_path->toBeNull()
        ->video_size->toBeNull()
        ->vimeo_status->toBe(VideoStatus::Uploading);

    // The half-uploaded file is no longer needed.
    Storage::disk('videos')->assertMissing($serverPath);
});

test('uploads go to Vimeo when this server is low on disk space', function (): void {
    config(['games.videos.reserved_disk_space' => PHP_INT_MAX]);
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['title' => 'My Game', 'player_id' => $user->id]);
    $this->actingAs($user);

    expectVimeoUploadCreated('My Game');
    Log::shouldReceive('warning')->once()->with(sprintf('Uploading the video for game %d to Vimeo because this server is low on disk space.', $game->id));

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ])->assertOk()->assertJson(['storage' => 'vimeo', 'upload_id' => '/videos/123456789']);

    expect($game->refresh()->vimeo_uri)->toBe('/videos/123456789');
});

test('uploads go to Vimeo when server uploads are switched off', function (): void {
    config(['games.videos.server_uploads' => false]);
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['title' => 'My Game', 'player_id' => $user->id]);
    $this->actingAs($user);

    expectVimeoUploadCreated('My Game');
    Log::shouldReceive('warning')->never();

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ])->assertOk()->assertJson(['storage' => 'vimeo', 'upload_id' => '/videos/123456789']);

    expect($game->refresh())
        ->vimeo_uri->toBe('/videos/123456789')
        ->vimeo_status->toBe(VideoStatus::Uploading);
});

function expectVimeoUploadCreated(string $title): void
{
    Vimeo::shouldReceive('request')
        ->once()
        ->with('/me/videos', [
            'upload' => [
                'approach' => 'tus',
                'size' => 1024000,
            ],
            'name' => $title,
        ], 'POST')
        ->andReturn([
            'body' => [
                'uri' => '/videos/123456789',
                'upload' => [
                    'upload_link' => 'https://asia-files.tus.vimeo.com/files/123456789',
                ],
            ],
        ]);
}

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

/**
 * @param  array<mixed>  $body
 */
function expectVimeoVideoLookup(string $uri, array $body, int $status = 200): void
{
    Vimeo::shouldReceive('request')
        ->once()
        ->with($uri, ['fields' => 'upload.status,transcode.status'], 'GET')
        ->andReturn(['status' => $status, 'body' => $body]);
}

function gameUploadingVideo(User $user, string $uri = '/videos/123456789'): Game
{
    return Game::factory()->create([
        'player_id' => $user->id,
        'vimeo_uri' => $uri,
        'vimeo_status' => VideoStatus::Uploading,
    ]);
}

test('complete upload marks the video ready once Vimeo has transcoded it', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user);
    $this->actingAs($user);

    expectVimeoVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'complete'],
        'transcode' => ['status' => 'complete'],
    ]);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/123456789'])
        ->assertOk()
        ->assertJson(['vimeo_status' => 'ready']);

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Ready);
});

test('complete upload marks the video processing while Vimeo is still transcoding', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user);
    $this->actingAs($user);

    expectVimeoVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'complete'],
        'transcode' => ['status' => 'in_progress'],
    ]);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/123456789'])
        ->assertOk()
        ->assertJson(['vimeo_status' => 'processing']);

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Processing);
});

test('complete upload is rejected while Vimeo has not received the whole file', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user);
    $this->actingAs($user);

    expectVimeoVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'in_progress'],
        'transcode' => ['status' => 'in_progress'],
    ]);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/123456789'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_id' => 'Vimeo has not received the whole video yet.']);

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Uploading);
});

test('complete upload records a failure reported by Vimeo', function (array $body, int $status): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user);
    $this->actingAs($user);

    expectVimeoVideoLookup('/videos/123456789', $body, $status);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/123456789'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_id' => 'Vimeo could not process this video. Please upload it again.']);

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Failed);
})->with([
    'upload error' => [['upload' => ['status' => 'error'], 'transcode' => ['status' => 'in_progress']], 200],
    'transcode error' => [['upload' => ['status' => 'complete'], 'transcode' => ['status' => 'error']], 200],
    'video deleted' => [['error' => "The requested video couldn't be found."], 404],
]);

test('complete upload leaves the status alone when Vimeo cannot be reached', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user);
    $this->actingAs($user);

    expectVimeoVideoLookup('/videos/123456789', [], 503);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/123456789'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_id' => 'We could not reach Vimeo to verify the upload. Please try again.']);

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Uploading);
});

test('a stale completion request cannot mark a newer upload complete', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user, '/videos/222');
    $this->actingAs($user);

    Vimeo::shouldReceive('request')->never();

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/111'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_id' => 'This upload has been replaced by a newer one.']);

    expect($game->refresh())
        ->vimeo_uri->toBe('/videos/222')
        ->vimeo_status->toBe(VideoStatus::Uploading);
});

test('a completion is discarded when a newer upload starts while Vimeo is being checked', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user, '/videos/111');
    $this->actingAs($user);

    Vimeo::shouldReceive('request')
        ->once()
        ->with('/videos/111', ['fields' => 'upload.status,transcode.status'], 'GET')
        ->andReturnUsing(function () use ($game): array {
            Game::query()->withoutGlobalScopes()->whereKey($game->id)->update([
                'vimeo_uri' => '/videos/222',
                'vimeo_status' => VideoStatus::Uploading->value,
            ]);

            return ['status' => 200, 'body' => ['upload' => ['status' => 'complete'], 'transcode' => ['status' => 'complete']]];
        });

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/111'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_id' => 'This upload has been replaced by a newer one.']);

    expect($game->refresh())
        ->vimeo_uri->toBe('/videos/222')
        ->vimeo_status->toBe(VideoStatus::Uploading);
});

test('complete upload is rejected when no upload was started', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    Vimeo::shouldReceive('request')->never();

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/111'])
        ->assertJsonValidationErrors('upload_id');

    expect($game->refresh()->vimeo_status)->toBeNull();
});

test('complete upload requires the upload attempt identifier', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user);
    $this->actingAs($user);

    $this->patchJson(route('admin.games.complete-upload', $game))
        ->assertJsonValidationErrors('upload_id');
});

test('users cannot complete an upload for another player game', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo(User::factory()->create());
    $this->actingAs($user);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/123456789'])
        ->assertForbidden();

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Uploading);
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

test('completing an upload sends a game held for its video to moderation', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = gameUploadingVideo($user);
    $game->update(['status' => GameStatus::AwaitingVideo]);
    GameResult::factory()->create(['game_id' => $game->id, 'confirmed_at' => now()]);
    $this->actingAs($user);

    expectVimeoVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'complete'],
        'transcode' => ['status' => 'complete'],
    ]);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => '/videos/123456789'])->assertOk();

    expect($game->refresh()->status)->toBe(GameStatus::Pending);
});

test('the video cannot be replaced after the game has been reviewed', function (GameStatus $status): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->withReadyVideo()->create(['player_id' => $user->id, 'status' => $status]);
    $this->actingAs($user);

    Vimeo::shouldReceive('request')->never();

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ])->assertUnprocessable()->assertJsonValidationErrors('file_size');

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Ready);
})->with([
    'approved' => GameStatus::Approved,
    'rejected' => GameStatus::Rejected,
    'flagged' => GameStatus::Flagged,
]);

test('replacing the video of a game in moderation takes it out of the queue until the new video is ready', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->withServerVideo()->create(['player_id' => $user->id, 'status' => GameStatus::Pending, 'title' => 'My Game']);
    $replacedPath = (string) $game->video_path;
    Storage::disk('videos')->put($replacedPath, 'old video');
    $this->actingAs($user);

    $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => 1024000,
        'file_name' => 'game-video.mp4',
    ])->assertOk();

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingVideo)
        ->and($game->vimeo_status)->toBe(VideoStatus::Uploading)
        ->and($game->video_path)->not->toBe($replacedPath);

    Storage::disk('videos')->assertMissing($replacedPath);
});

test('editing a scheduled game records it as played and awaiting its result', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->scheduled()->againstPlayer()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $this->patch(route('admin.games.update', $game), [
        'title' => 'Played Now',
        'format' => '1v1',
        'played_at' => '2026-02-01T14:00:00+00:00',
    ])->assertRedirect(route('admin.games.index'));

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingResult);
});
