<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\VideoStatus;
use App\Exceptions\VimeoUnreachableException;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class CompleteUploadAction
{
    public function __construct(private FetchVimeoVideoStatusAction $fetchVimeoVideoStatus) {}

    /**
     * Mark an upload attempt finished, trusting the stored file or Vimeo rather than the browser.
     *
     * @throws ValidationException when the attempt is stale, unfinished or failed
     */
    public function handle(Game $game, string $uploadId): VideoStatus
    {
        $this->ensureCurrentAttempt($game, $uploadId);

        $storedOnServer = $game->video_path !== null;
        $status = $storedOnServer
            ? $this->serverVideoStatus((string) $game->video_path, (int) $game->video_size)
            : $this->vimeoVideoStatus($uploadId);

        DB::transaction(function () use ($game, $uploadId, $status): void {
            $locked = Game::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($game->id);

            // A newer upload may have started while we were checking this one.
            $this->ensureCurrentAttempt($locked, $uploadId);

            $locked->update(['vimeo_status' => $status]);
            $locked->releaseToModerationIfVideoReady();
        });

        if ($status === VideoStatus::Failed) {
            throw ValidationException::withMessages(['upload_id' => $storedOnServer
                ? 'This file is not a video. Please upload a video file.'
                : 'Vimeo could not process this video. Please upload it again.']);
        }

        return $status;
    }

    private function ensureCurrentAttempt(Game $game, string $uploadId): void
    {
        if ($game->videoUploadId() !== $uploadId) {
            throw ValidationException::withMessages(['upload_id' => 'This upload has been replaced by a newer one.']);
        }
    }

    /** This server has no transcoding step, so a whole video file is ready straight away. */
    private function serverVideoStatus(string $path, int $expectedSize): VideoStatus
    {
        $disk = Storage::disk('videos');

        if ($disk->size($path) < $expectedSize) {
            throw ValidationException::withMessages(['upload_id' => 'The server has not received the whole video yet.']);
        }

        return str_starts_with((string) $disk->mimeType($path), 'video/') ? VideoStatus::Ready : VideoStatus::Failed;
    }

    private function vimeoVideoStatus(string $vimeoUri): VideoStatus
    {
        try {
            $status = $this->fetchVimeoVideoStatus->handle($vimeoUri);
        } catch (VimeoUnreachableException) {
            throw ValidationException::withMessages(['upload_id' => 'We could not reach Vimeo to verify the upload. Please try again.']);
        }

        if ($status === VideoStatus::Uploading) {
            throw ValidationException::withMessages(['upload_id' => 'Vimeo has not received the whole video yet.']);
        }

        return $status;
    }
}
