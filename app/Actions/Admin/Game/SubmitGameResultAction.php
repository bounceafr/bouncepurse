<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Actions\Game\NotifyGameActivityAction;
use App\Enums\GameActivity;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class SubmitGameResultAction
{
    public function __construct(private NotifyGameActivityAction $notify) {}

    /**
     * @param  array{your_score: int, opponent_score: int, started_at: string, finished_at: string}  $data
     */
    public function handle(Game $game, User $submitter, array $data): GameResult
    {
        try {
            $result = DB::transaction(function () use ($game, $submitter, $data): GameResult {
                $locked = Game::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($game->id);

                $blocker = $locked->resultSubmissionBlocker();

                if ($blocker !== null) {
                    throw ValidationException::withMessages(['result' => $blocker]);
                }

                $result = GameResult::query()->create([
                    'uuid' => Str::uuid(),
                    'game_id' => $locked->id,
                    'submitter_id' => $submitter->id,
                    'started_at' => $data['started_at'],
                    'finished_at' => $data['finished_at'],
                    'your_score' => $data['your_score'],
                    'opponent_score' => $data['opponent_score'],
                ]);

                $locked->update([
                    'played_at' => $data['finished_at'],
                    'status' => GameStatus::AwaitingConfirmation,
                    'result' => $result->outcome(),
                ]);

                return $result;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['result' => 'A result has already been submitted for this game.']);
        }

        $this->notify->handle($game->refresh(), GameActivity::ResultSubmitted);

        return $result;
    }
}
