<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Admin\Game\FetchVimeoVideoStatusAction;
use App\Enums\VideoStatus;
use App\Exceptions\VimeoUnreachableException;
use App\Models\Game;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

#[Description('Re-check videos Vimeo is still transcoding and mark them ready or failed')]
#[Signature('app:sync-processing-videos')]
final class SyncProcessingVideosCommand extends Command
{
    public function handle(FetchVimeoVideoStatusAction $fetchVimeoVideoStatus): int
    {
        Game::query()
            ->withoutGlobalScopes()
            ->where('vimeo_status', VideoStatus::Processing)
            ->whereNotNull('vimeo_uri')
            ->chunkById(100, function (Collection $games) use ($fetchVimeoVideoStatus): void {
                /** @var Collection<int, Game> $games */
                foreach ($games as $game) {
                    $this->sync($game, $fetchVimeoVideoStatus);
                }
            });

        return self::SUCCESS;
    }

    private function sync(Game $game, FetchVimeoVideoStatusAction $fetchVimeoVideoStatus): void
    {
        /** @var string $vimeoUri */
        $vimeoUri = $game->vimeo_uri;

        try {
            $status = $fetchVimeoVideoStatus->handle($vimeoUri);
        } catch (VimeoUnreachableException $vimeoUnreachableException) {
            $this->warn(sprintf('Skipped game %d: %s', $game->id, $vimeoUnreachableException->getMessage()));

            return;
        }

        if (! in_array($status, [VideoStatus::Ready, VideoStatus::Failed], true)) {
            return;
        }

        $updated = DB::transaction(function () use ($game, $vimeoUri, $status): bool {
            $locked = Game::query()->withoutGlobalScopes()->lockForUpdate()->find($game->id);

            // Only move the game on if no newer upload replaced this video while we were asking Vimeo.
            if ($locked === null || $locked->vimeo_uri !== $vimeoUri || $locked->vimeo_status !== VideoStatus::Processing) {
                return false;
            }

            $locked->update(['vimeo_status' => $status]);
            $locked->releaseToModerationIfVideoReady();

            return true;
        });

        if ($updated) {
            $this->info(sprintf('Game %d video is now %s.', $game->id, $status->value));
        }
    }
}
