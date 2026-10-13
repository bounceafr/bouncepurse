<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Validation\ValidationException;

final class UpdateAction
{
    /** @param array<string, mixed> $data */
    public function handle(Game $game, array $data): Game
    {
        if (! $game->status->isOpenForResult() || $game->gameResult()->exists()) {
            throw ValidationException::withMessages(['game' => 'A game cannot be edited after its result has been submitted.']);
        }

        // The edit form always records when the game was played.
        if (isset($data['played_at'])) {
            $data['status'] = GameStatus::AwaitingResult;
        }

        $game->update($data);
        $game->refresh();

        return $game;
    }
}
