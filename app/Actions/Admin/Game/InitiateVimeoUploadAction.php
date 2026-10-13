<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\VideoStatus;
use App\Models\Game;
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
        ]);

        return [
            'upload_link' => $body['upload']['upload_link'],
            'vimeo_uri' => $body['uri'],
        ];
    }
}
