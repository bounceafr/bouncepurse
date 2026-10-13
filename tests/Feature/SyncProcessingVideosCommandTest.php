<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\VideoStatus;
use App\Models\Game;
use App\Models\GameResult;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;
use Vimeo\Laravel\Facades\Vimeo;

/**
 * @param  array<mixed>  $body
 */
function expectProcessingVideoLookup(string $uri, array $body, int $status = 200): void
{
    Vimeo::shouldReceive('request')
        ->once()
        ->with($uri, ['fields' => 'upload.status,transcode.status'], 'GET')
        ->andReturn(['status' => $status, 'body' => $body]);
}

function syncProcessingVideos(TestCase $test): PendingCommand
{
    $command = $test->artisan('app:sync-processing-videos');
    assert($command instanceof PendingCommand);

    return $command;
}

function gameProcessingVideo(string $uri = '/videos/123456789'): Game
{
    return Game::factory()->create([
        'vimeo_uri' => $uri,
        'vimeo_status' => VideoStatus::Processing,
    ]);
}

it('marks processing videos ready once Vimeo has transcoded them', function (): void {
    $game = gameProcessingVideo();

    expectProcessingVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'complete'],
        'transcode' => ['status' => 'complete'],
    ]);

    syncProcessingVideos($this)
        ->expectsOutput(sprintf('Game %d video is now ready.', $game->id))
        ->assertSuccessful();

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Ready);
});

it('marks processing videos failed when Vimeo reports a transcode error', function (): void {
    $game = gameProcessingVideo();

    expectProcessingVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'complete'],
        'transcode' => ['status' => 'error'],
    ]);

    syncProcessingVideos($this)->assertSuccessful();

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Failed);
});

it('marks processing videos failed when Vimeo no longer has them', function (): void {
    $game = gameProcessingVideo();

    expectProcessingVideoLookup('/videos/123456789', ['error' => 'not found'], 404);

    syncProcessingVideos($this)->assertSuccessful();

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Failed);
});

it('leaves videos Vimeo is still transcoding as processing', function (): void {
    $game = gameProcessingVideo();

    expectProcessingVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'complete'],
        'transcode' => ['status' => 'in_progress'],
    ]);

    syncProcessingVideos($this)
        ->doesntExpectOutputToContain('is now')
        ->assertSuccessful();

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Processing);
});

it('leaves videos unchanged when Vimeo cannot be reached', function (): void {
    $game = gameProcessingVideo();

    expectProcessingVideoLookup('/videos/123456789', [], 503);

    syncProcessingVideos($this)
        ->expectsOutput(sprintf('Skipped game %d: Vimeo responded with HTTP 503.', $game->id))
        ->assertSuccessful();

    expect($game->refresh()->vimeo_status)->toBe(VideoStatus::Processing);
});

it('only checks games whose video is processing', function (): void {
    Game::factory()->create(['vimeo_uri' => '/videos/1', 'vimeo_status' => VideoStatus::Uploading]);
    Game::factory()->create(['vimeo_uri' => '/videos/2', 'vimeo_status' => VideoStatus::Ready]);
    Game::factory()->create(['vimeo_uri' => '/videos/3', 'vimeo_status' => VideoStatus::Failed]);
    Game::factory()->create();

    Vimeo::shouldReceive('request')->never();

    syncProcessingVideos($this)->assertSuccessful();
});

it('does not overwrite a game whose video was replaced during the check', function (): void {
    $game = gameProcessingVideo();

    Vimeo::shouldReceive('request')
        ->once()
        ->andReturnUsing(function () use ($game): array {
            Game::query()->whereKey($game->id)->update([
                'vimeo_uri' => '/videos/987654321',
                'vimeo_status' => VideoStatus::Uploading,
            ]);

            return ['status' => 200, 'body' => ['upload' => ['status' => 'complete'], 'transcode' => ['status' => 'complete']]];
        });

    syncProcessingVideos($this)
        ->doesntExpectOutputToContain('is now')
        ->assertSuccessful();

    expect($game->refresh())
        ->vimeo_uri->toBe('/videos/987654321')
        ->vimeo_status->toBe(VideoStatus::Uploading);
});

it('is scheduled every five minutes', function (): void {
    $event = collect(resolve(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'app:sync-processing-videos'));

    expect($event)->toBeInstanceOf(Event::class)
        ->and($event?->expression)->toBe('*/5 * * * *');
});

it('sends a game held for its video to moderation once the video is ready', function (): void {
    $game = gameProcessingVideo();
    $game->update(['status' => GameStatus::AwaitingVideo]);
    GameResult::factory()->create(['game_id' => $game->id, 'rejected_at' => now(), 'rejection_reason' => 'Wrong score.']);

    expectProcessingVideoLookup('/videos/123456789', [
        'upload' => ['status' => 'complete'],
        'transcode' => ['status' => 'complete'],
    ]);

    syncProcessingVideos($this)->assertSuccessful();

    expect($game->refresh()->status)->toBe(GameStatus::Contested);
});
