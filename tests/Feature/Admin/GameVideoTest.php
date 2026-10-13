<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Enums\VideoStatus;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Vimeo\Laravel\Facades\Vimeo;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'view-games']);
    Storage::fake('videos');
    config(['games.videos.reserved_disk_space' => 0]);
});

/** The smallest file recognised as an MP4 video. */
function mp4VideoBytes(): string
{
    return "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom";
}

function videoUploader(): User
{
    return User::factory()->create()->givePermissionTo('view-games');
}

/** A game whose video this server is receiving, holding the bytes received so far. */
function gameReceivingServerVideo(User $user, string $received = '', int $size = 24): Game
{
    $game = Game::factory()->create([
        'player_id' => $user->id,
        'video_path' => 'game/'.Str::lower((string) Str::ulid()).'.mp4',
        'video_size' => $size,
        'vimeo_status' => VideoStatus::Uploading,
    ]);

    Storage::disk('videos')->put((string) $game->video_path, $received);

    return $game;
}

/** @return TestResponse<Response> */
function sendVideoChunk(TestCase $test, Game $game, ?string $offset, string $bytes): TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/offset+octet-stream'];

    if ($offset !== null) {
        $server['HTTP_UPLOAD_OFFSET'] = $offset;
    }

    return $test->call('PATCH', route('admin.games.video-upload.chunk', [$game, (string) $game->videoUploadId()]), server: $server, content: $bytes);
}

function receivedVideoBytes(Game $game): ?string
{
    return Storage::disk('videos')->get((string) $game->video_path);
}

test('the upload offset tells the client how much of the video this server has received', function (): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user, 'abcd');
    $this->actingAs($user);

    $this->call('HEAD', route('admin.games.video-upload.offset', [$game, (string) $game->videoUploadId()]))
        ->assertOk()
        ->assertHeader('Upload-Offset', '4')
        ->assertHeader('Upload-Length', '24')
        ->assertHeader('Tus-Resumable', '1.0.0');
});

test('chunks are appended until the whole video has been received', function (): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user);
    $this->actingAs($user);

    sendVideoChunk($this, $game, '0', mb_substr(mp4VideoBytes(), 0, 10))
        ->assertNoContent()
        ->assertHeader('Upload-Offset', '10')
        ->assertHeader('Tus-Resumable', '1.0.0');

    sendVideoChunk($this, $game, '10', mb_substr(mp4VideoBytes(), 10))
        ->assertNoContent()
        ->assertHeader('Upload-Offset', '24');

    expect(receivedVideoBytes($game))->toBe(mp4VideoBytes());
});

test('a chunk sent at the wrong offset is refused, so the client re-reads the offset', function (): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user, 'abcd');
    $this->actingAs($user);

    sendVideoChunk($this, $game, '0', 'wxyz')->assertStatus(409);

    expect(receivedVideoBytes($game))->toBe('abcd');
});

test('a chunk without a valid offset is refused', function (?string $offset): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user);
    $this->actingAs($user);

    sendVideoChunk($this, $game, $offset, 'abcd')->assertStatus(400);

    expect(receivedVideoBytes($game))->toBe('');
})->with([
    'missing' => [null],
    'negative' => ['-1'],
    'not a number' => ['abc'],
]);

test('this server never stores more than the declared video size', function (): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user, size: 4);
    $this->actingAs($user);

    sendVideoChunk($this, $game, '0', 'abcdefgh')->assertHeader('Upload-Offset', '4');

    expect(receivedVideoBytes($game))->toBe('abcd');
});

test('upload requests for a video this server is not receiving are not found', function (string $method, string $change): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user);
    $uploadId = (string) $game->videoUploadId();
    $game->update(match ($change) {
        'replaced by a newer upload' => ['video_path' => 'game/'.Str::lower((string) Str::ulid()).'.mp4'],
        'already received' => ['vimeo_status' => VideoStatus::Ready],
        'moved to Vimeo' => ['video_path' => null, 'vimeo_uri' => '/videos/1'],
        default => throw new InvalidArgumentException('Unknown change: '.$change),
    });
    $this->actingAs($user);

    $this->call($method, route('admin.games.video-upload.offset', [$game, $uploadId]), server: ['HTTP_UPLOAD_OFFSET' => '0'], content: 'abcd')
        ->assertNotFound();
})->with(['HEAD', 'PATCH'])->with(['replaced by a newer upload', 'already received', 'moved to Vimeo']);

test('only someone who may upload the video can send it', function (string $method): void {
    $game = gameReceivingServerVideo(User::factory()->create());
    $this->actingAs(videoUploader());

    $this->call($method, route('admin.games.video-upload.offset', [$game, (string) $game->videoUploadId()]), server: ['HTTP_UPLOAD_OFFSET' => '0'], content: 'abcd')
        ->assertForbidden();

    expect(receivedVideoBytes($game))->toBe('');
})->with(['HEAD', 'PATCH']);

test('completing a server upload makes the video ready and sends a held game to moderation', function (): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user, mp4VideoBytes());
    $game->update(['status' => GameStatus::AwaitingVideo]);
    GameResult::factory()->create(['game_id' => $game->id, 'confirmed_at' => now()]);
    $this->actingAs($user);

    Vimeo::shouldReceive('request')->never();

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => $game->videoUploadId()])
        ->assertOk()
        ->assertJson(['vimeo_status' => 'ready']);

    expect($game->refresh())
        ->vimeo_status->toBe(VideoStatus::Ready)
        ->status->toBe(GameStatus::Pending);
});

test('a server upload cannot be completed before the whole video has arrived', function (): void {
    $user = videoUploader();
    $game = gameReceivingServerVideo($user, 'abcd');
    $this->actingAs($user);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => $game->videoUploadId()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_id' => 'The server has not received the whole video yet.']);

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Uploading);
});

test('a server upload that is not a video is marked failed', function (): void {
    $user = videoUploader();
    $page = '<html><body>Not a video</body></html>';
    $game = gameReceivingServerVideo($user, $page, mb_strlen($page));
    $this->actingAs($user);

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => $game->videoUploadId()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['upload_id' => 'This file is not a video. Please upload a video file.']);

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Failed);
});

test('a video uploaded to this server can be watched and sought through', function (): void {
    $user = videoUploader();
    $game = Game::factory()->withServerVideo()->create(['player_id' => $user->id]);
    Storage::disk('videos')->put((string) $game->video_path, mp4VideoBytes());
    $this->actingAs($user);

    $this->get(route('admin.games.video', $game))
        ->assertOk()
        ->assertHeader('Content-Type', 'video/mp4')
        ->assertHeader('Content-Length', '24')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->get(route('admin.games.video', $game), ['Range' => 'bytes=0-3'])
        ->assertStatus(206)
        ->assertHeader('Content-Range', 'bytes 0-3/24');
});

test('a video this server does not hold ready cannot be watched', function (string $video): void {
    $user = videoUploader();
    $game = Game::factory()->create([
        'player_id' => $user->id,
        ...match ($video) {
            'still uploading' => ['video_path' => 'game/video.mp4', 'vimeo_status' => VideoStatus::Uploading],
            'stored on Vimeo' => ['vimeo_uri' => '/videos/1', 'vimeo_status' => VideoStatus::Ready],
            'none' => [],
            default => throw new InvalidArgumentException('Unknown video: '.$video),
        },
    ]);
    $this->actingAs($user);

    $this->get(route('admin.games.video', $game))->assertNotFound();
})->with(['still uploading', 'stored on Vimeo', 'none']);

test('players can watch the videos of their own games only', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $opponent = User::factory()->create()->assignRole(Role::Player->value);
    $outsider = User::factory()->create()->assignRole(Role::Player->value);
    $game = Game::factory()->againstPlayer($opponent)->withServerVideo()->create();
    Storage::disk('videos')->put((string) $game->video_path, mp4VideoBytes());

    $this->actingAs($opponent)->get(route('admin.games.video', $game))->assertOk();
    $this->actingAs($outsider)->get(route('admin.games.video', $game))->assertNotFound();
});

test('a video travels from the browser to the moderation queue through this server', function (): void {
    $user = videoUploader();
    $game = Game::factory()->create(['player_id' => $user->id, 'status' => GameStatus::AwaitingVideo]);
    GameResult::factory()->create(['game_id' => $game->id, 'confirmed_at' => now()]);
    $this->actingAs($user);

    $upload = $this->postJson(route('admin.games.upload-url', $game), [
        'file_size' => mb_strlen(mp4VideoBytes()),
        'file_name' => 'final.mp4',
    ])->assertOk()->assertJson(['storage' => 'server']);

    /** @var string $uploadLink */
    $uploadLink = $upload->json('upload_link');

    $this->call('HEAD', $uploadLink)->assertHeader('Upload-Offset', '0');
    $this->call('PATCH', $uploadLink, server: ['HTTP_UPLOAD_OFFSET' => '0'], content: mp4VideoBytes())
        ->assertHeader('Upload-Offset', '24');

    $this->patchJson(route('admin.games.complete-upload', $game), ['upload_id' => $upload->json('upload_id')])
        ->assertJson(['vimeo_status' => 'ready']);

    expect($game->refresh()->status)->toBe(GameStatus::Pending);
    $this->get(route('admin.games.video', $game))->assertOk();
});
