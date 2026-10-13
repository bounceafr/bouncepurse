<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Game\NotifyGameActivityAction;
use App\Enums\GameActivity;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameResult;
use DateTimeInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

#[Description('Remind opponents who have not confirmed a result, and send unanswered results to moderators')]
#[Signature('app:process-result-confirmations')]
final class ProcessResultConfirmationsCommand extends Command
{
    public function handle(NotifyGameActivityAction $notify): int
    {
        $remindBefore = now()->subHours(Config::integer('games.result_confirmation.remind_after_hours'));
        $expireBefore = now()->subHours(Config::integer('games.result_confirmation.expire_after_hours'));

        // Expire first so a long-overdue result is not reminded and expired in the same run.
        $this->awaiting($expireBefore)->chunkById(100, function (Collection $games) use ($notify): void {
            /** @var Collection<int, Game> $games */
            foreach ($games as $game) {
                $this->expire($game, $notify);
            }
        });

        $this->awaiting($remindBefore)
            ->whereHas('gameResult', fn (Builder $query) => $query->whereNull('confirmation_reminded_at'))
            ->chunkById(100, function (Collection $games) use ($notify): void {
                /** @var Collection<int, Game> $games */
                foreach ($games as $game) {
                    $this->remind($game, $notify);
                }
            });

        return self::SUCCESS;
    }

    /** @return Builder<Game> */
    private function awaiting(DateTimeInterface $submittedBefore): Builder
    {
        return Game::query()
            ->withoutGlobalScopes()
            ->where('status', GameStatus::AwaitingConfirmation)
            ->whereHas('gameResult', fn (Builder $query) => $query->where('created_at', '<=', $submittedBefore));
    }

    private function remind(Game $game, NotifyGameActivityAction $notify): void
    {
        $reminded = GameResult::query()
            ->where('game_id', $game->id)
            ->whereNull('confirmation_reminded_at')
            ->update(['confirmation_reminded_at' => now()]);

        // Another run may have reminded this game already.
        if ($reminded > 0) {
            $notify->handle($game, GameActivity::ConfirmationReminder);
            $this->info(sprintf('Reminded the opponent in game %d.', $game->id));
        }
    }

    private function expire(Game $game, NotifyGameActivityAction $notify): void
    {
        $expired = DB::transaction(function () use ($game): bool {
            $locked = Game::query()->withoutGlobalScopes()->lockForUpdate()->find($game->id);

            // The opponent may have answered since this game was selected.
            if ($locked?->status !== GameStatus::AwaitingConfirmation || $locked->gameResult === null) {
                return false;
            }

            $locked->gameResult->update(['confirmation_expired_at' => now()]);
            $locked->update(['status' => $locked->moderationEntryStatus()]);

            return true;
        });

        if ($expired) {
            $notify->handle($game->refresh(), GameActivity::ConfirmationExpired);
            $this->info(sprintf('Game %d was not confirmed in time and went to moderation.', $game->id));
        }
    }
}
