<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Enums\VideoStatus;
use App\Models\Game;
use Illuminate\Validation\ValidationException;
use Vimeo\Laravel\Facades\Vimeo;

final class InitiateVimeoUploadAction
{
    /**
     * The returned vimeo_uri identifies this upload attempt; the client must send it back on completion.
     *
     * @return array{upload_link: string, vimeo_uri: string}
     */
    public function handle(Game $game, int $fileSize): array
    {
        // The video is the evidence a moderator decided on; it must not change afterwards.
        if ($game->status->isReviewed()) {
            throw ValidationException::withMessages(['file_size' => 'The video cannot be replaced after the game has been reviewed.']);
        }

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

        $game->update([
            'vimeo_uri' => $body['uri'],
            'vimeo_status' => VideoStatus::Uploading,
            // A game waiting for a moderator goes back to waiting for its new video.
            ...in_array($game->status, [GameStatus::Pending, GameStatus::Contested], true)
                ? ['status' => GameStatus::AwaitingVideo]
                : [],
        ]);

        return [
            'upload_link' => $body['upload']['upload_link'],
            'vimeo_uri' => $body['uri'],
        ];
    }
}
