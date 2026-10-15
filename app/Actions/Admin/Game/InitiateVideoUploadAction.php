<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Enums\VideoStatus;
use App\Models\Game;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Vimeo\Laravel\Facades\Vimeo;

final class InitiateVideoUploadAction
{
    /**
     * Start an upload attempt on this server, or on Vimeo when the server cannot take the video.
     *
     * Both are resumable tus uploads. The returned upload_id identifies this attempt; the client must send it back on completion.
     *
     * @param  bool  $failover  the browser could not finish uploading to this server
     * @return array{storage: 'server'|'vimeo', upload_link: string, upload_id: string}
     */
    public function handle(Game $game, int $fileSize, string $fileName, bool $failover = false): array
    {
        // The video is the evidence a moderator decided on; it must not change afterwards.
        if ($game->status->isReviewed()) {
            throw ValidationException::withMessages(['file_size' => 'The video cannot be replaced after the game has been reviewed.']);
        }

        $replacedPath = $game->video_path;

        $upload = $this->serverCanStore($game, $fileSize, $failover)
            ? $this->startServerUpload($game, $fileSize, $fileName)
            : $this->startVimeoUpload($game, $fileSize);

        // A replaced video is no longer evidence for anything, so free its space.
        if ($replacedPath !== null) {
            Storage::disk('videos')->delete($replacedPath);
        }

        return $upload;
    }

    private function serverCanStore(Game $game, int $fileSize, bool $failover): bool
    {
        if (! Config::boolean('games.videos.server_uploads')) {
            return false;
        }

        $reason = match (true) {
            $failover => 'the upload to this server failed',
            ! $this->hasSpaceFor($fileSize) => 'this server is low on disk space',
            default => null,
        };

        if ($reason === null) {
            return true;
        }

        Log::warning(sprintf('Uploading the video for game %d to Vimeo because %s.', $game->id, $reason));

        return false;
    }

    private function hasSpaceFor(int $fileSize): bool
    {
        $free = disk_free_space(Storage::disk('videos')->path(''));

        return $free !== false && $free - $fileSize >= Config::integer('games.videos.reserved_disk_space');
    }

    /** @return array{storage: 'server', upload_link: string, upload_id: string} */
    private function startServerUpload(Game $game, int $fileSize, string $fileName): array
    {
        $uploadId = Str::lower((string) Str::ulid());
        // Keep a plain extension so the file type can still be told when its contents are inconclusive.
        $extension = Str::lower(pathinfo($fileName, PATHINFO_EXTENSION));
        $path = $game->uuid.'/'.$uploadId.(preg_match('/^[a-z0-9]{1,10}$/', $extension) === 1 ? '.'.$extension : '');

        Storage::disk('videos')->put($path, '');

        $this->startAttempt($game, ['vimeo_uri' => null, 'video_path' => $path, 'video_size' => $fileSize]);

        return [
            'storage' => 'server',
            'upload_link' => route('admin.games.video-upload.offset', [$game, $uploadId]),
            'upload_id' => $uploadId,
        ];
    }

    /** @return array{storage: 'vimeo', upload_link: string, upload_id: string} */
    private function startVimeoUpload(Game $game, int $fileSize): array
    {
        // @phpstan-ignore staticMethod.notFound
        $response = Vimeo::request('/me/videos', [
            'upload' => [
                'approach' => 'tus',
                'size' => $fileSize,
            ],
            'name' => $game->title,
        ], 'POST');

        /** @var array{body: array{uri: string, upload: array{upload_link: string}}} $response */
        $body = $response['body'];

        $this->startAttempt($game, ['vimeo_uri' => $body['uri'], 'video_path' => null, 'video_size' => null]);

        return [
            'storage' => 'vimeo',
            'upload_link' => $body['upload']['upload_link'],
            'upload_id' => $body['uri'],
        ];
    }

    /** @param  array<string, mixed>  $attributes */
    private function startAttempt(Game $game, array $attributes): void
    {
        $game->update([
            ...$attributes,
            'vimeo_status' => VideoStatus::Uploading,
            // A game waiting for a moderator goes back to waiting for its new video.
            ...in_array($game->status, [GameStatus::Pending, GameStatus::Contested], true)
                ? ['status' => GameStatus::AwaitingVideo]
                : [],
        ]);
    }
}
