<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameResult;
use Illuminate\Validation\ValidationException;

final readonly class LockGameAwaitingConfirmation
{
    /**
     * Lock the game row and its result; must be called inside a transaction.
     *
     * @return array{Game, GameResult}
     */
    public function handle(Game $game): array
    {
        $locked = Game::query()->withoutGlobalScopes()->lockForUpdate()->findOrFail($game->id);
        $result = GameResult::query()->where('game_id', $locked->id)->lockForUpdate()->first();

        if ($locked->status !== GameStatus::AwaitingConfirmation || ! $result instanceof GameResult) {
            throw ValidationException::withMessages(['result' => 'This result is no longer awaiting confirmation.']);
        }

        return [$locked, $result];
    }
}
