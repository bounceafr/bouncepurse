<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\VideoStatus;
use App\Exceptions\VimeoUnreachableException;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CompleteUploadAction
{
    public function __construct(private FetchVimeoVideoStatusAction $fetchVimeoVideoStatus) {}

    /**
     * Mark an upload attempt finished, trusting Vimeo rather than the browser.
     *
     * @throws ValidationException when the attempt is stale, unfinished or failed
     */
    public function handle(Game $game, string $vimeoUri): VideoStatus
    {
        $this->ensureCurrentAttempt($game, $vimeoUri);

        $status = $this->fetchStatus($vimeoUri);

        DB::transaction(function () use ($game, $vimeoUri, $status): void {
            $locked = Game::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($game->id);

            // A newer upload may have started while we were asking Vimeo about this one.
            $this->ensureCurrentAttempt($locked, $vimeoUri);

            $locked->update(['vimeo_status' => $status]);
            $locked->releaseToModerationIfVideoReady();
        });

        if ($status === VideoStatus::Failed) {
            throw ValidationException::withMessages(['vimeo_uri' => 'Vimeo could not process this video. Please upload it again.']);
        }

        return $status;
    }

    private function ensureCurrentAttempt(Game $game, string $vimeoUri): void
    {
        if ($game->vimeo_uri === null || $game->vimeo_uri !== $vimeoUri) {
            throw ValidationException::withMessages(['vimeo_uri' => 'This upload has been replaced by a newer one.']);
        }
    }

    private function fetchStatus(string $vimeoUri): VideoStatus
    {
        try {
            $status = $this->fetchVimeoVideoStatus->handle($vimeoUri);
        } catch (VimeoUnreachableException) {
            throw ValidationException::withMessages(['vimeo_uri' => 'We could not reach Vimeo to verify the upload. Please try again.']);
        }

        if ($status === VideoStatus::Uploading) {
            throw ValidationException::withMessages(['vimeo_uri' => 'Vimeo has not received the whole video yet.']);
        }

        return $status;
    }
}
