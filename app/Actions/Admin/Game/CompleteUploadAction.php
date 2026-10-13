<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\VideoStatus;
use App\Models\Game;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Vimeo\Laravel\Facades\Vimeo;

final class CompleteUploadAction
{
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
        // @phpstan-ignore staticMethod.notFound
        $response = Vimeo::request($vimeoUri, ['fields' => 'upload.status,transcode.status'], 'GET');

        /** @var array{status: int, body: array{upload?: array{status?: string}, transcode?: array{status?: string}}} $response */
        if ($response['status'] === 404) {
            return VideoStatus::Failed;
        }

        if ($response['status'] !== 200) {
            throw ValidationException::withMessages(['vimeo_uri' => 'We could not reach Vimeo to verify the upload. Please try again.']);
        }

        $upload = $response['body']['upload']['status'] ?? null;
        $transcode = $response['body']['transcode']['status'] ?? null;

        if ($upload === 'error' || $transcode === 'error') {
            return VideoStatus::Failed;
        }

        if ($upload !== 'complete') {
            throw ValidationException::withMessages(['vimeo_uri' => 'Vimeo has not received the whole video yet.']);
        }

        return $transcode === 'complete' ? VideoStatus::Ready : VideoStatus::Processing;
    }
}
