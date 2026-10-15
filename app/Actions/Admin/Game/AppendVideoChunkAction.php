<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class AppendVideoChunkAction
{
    /**
     * Append the next chunk of a video being uploaded to this server and return the new offset.
     *
     * @param  resource  $chunk  PHP cannot declare a resource type natively
     */
    public function handle(Game $game, int $offset, mixed $chunk): int
    {
        /** @var string $videoPath */
        $videoPath = $game->video_path;
        $file = fopen(Storage::disk('videos')->path($videoPath), 'cb') ?: throw new RuntimeException('The video upload could not be opened.');

        // Hold the file while appending, so a retried request cannot write in between.
        flock($file, LOCK_EX);

        try {
            fseek($file, 0, SEEK_END);

            // The client is out of step, e.g. after a timed-out chunk; tus clients re-read the offset on 409.
            abort_if(ftell($file) !== $offset, 409, 'The upload offset does not match what the server has received.');

            // Never store more than the size declared when the upload started.
            stream_copy_to_stream($chunk, $file, (int) $game->video_size - $offset);
            fflush($file);

            return (int) ftell($file);
        } finally {
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
}
