<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\VideoStatus;
use App\Exceptions\VimeoUnreachableException;
use Vimeo\Laravel\Facades\Vimeo;

final class FetchVimeoVideoStatusAction
{
    /**
     * Ask Vimeo where a video is in its lifecycle.
     *
     * Returns Uploading while Vimeo has not received the whole file, and Failed
     * when the video errored or no longer exists.
     *
     * @throws VimeoUnreachableException when Vimeo answers with anything other than 200 or 404
     */
    public function handle(string $vimeoUri): VideoStatus
    {
        // @phpstan-ignore staticMethod.notFound
        $response = Vimeo::request($vimeoUri, ['fields' => 'upload.status,transcode.status'], 'GET');

        /** @var array{status: int, body: array{upload?: array{status?: string}, transcode?: array{status?: string}}} $response */
        if ($response['status'] === 404) {
            return VideoStatus::Failed;
        }

        if ($response['status'] !== 200) {
            throw new VimeoUnreachableException($response['status']);
        }

        $upload = $response['body']['upload']['status'] ?? null;
        $transcode = $response['body']['transcode']['status'] ?? null;

        if ($upload === 'error' || $transcode === 'error') {
            return VideoStatus::Failed;
        }

        if ($upload !== 'complete') {
            return VideoStatus::Uploading;
        }

        return $transcode === 'complete' ? VideoStatus::Ready : VideoStatus::Processing;
    }
}
