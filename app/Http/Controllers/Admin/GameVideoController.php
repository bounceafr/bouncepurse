<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\Game\AppendVideoChunkAction;
use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Videos stored on this server: streaming them, and receiving them over the tus resumable upload protocol. */
final class GameVideoController extends Controller
{
    private const string TUS_VERSION = '1.0.0';

    /** Range requests are supported, so the player can seek. */
    public function show(Game $game): BinaryFileResponse
    {
        Gate::authorize('view', $game);

        abort_unless($game->video_path !== null && $game->vimeo_status === VideoStatus::Ready, 404);

        $disk = Storage::disk('videos');

        return response()->file($disk->path($game->video_path), [
            'Content-Type' => $disk->mimeType($game->video_path),
            // Uploads are user content; never let a browser treat one as anything but a video.
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** tus HEAD: how much of the upload this server has received, so the client can resume. */
    public function uploadOffset(Game $game, string $upload): Response
    {
        Gate::authorize('uploadVideo', $game);

        abort_unless($game->isReceivingServerUpload($upload), 404);

        return response('', 200, [
            'Upload-Offset' => (string) Storage::disk('videos')->size((string) $game->video_path),
            'Upload-Length' => (string) $game->video_size,
            'Tus-Resumable' => self::TUS_VERSION,
            'Cache-Control' => 'no-store',
        ]);
    }

    /** tus PATCH: append the next chunk at the offset the client says it is at. */
    public function uploadChunk(Request $request, AppendVideoChunkAction $action, Game $game, string $upload): Response
    {
        Gate::authorize('uploadVideo', $game);

        abort_unless($game->isReceivingServerUpload($upload), 404);

        $offset = filter_var($request->header('Upload-Offset'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        abort_if($offset === false, 400, 'The Upload-Offset header is missing or invalid.');

        $received = $action->handle($game, $offset, $request->getContent(true));

        return response()->noContent(204, [
            'Upload-Offset' => (string) $received,
            'Tus-Resumable' => self::TUS_VERSION,
        ]);
    }
}
