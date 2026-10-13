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
        if ($game->status !== GameStatus::Scheduled || $game->gameResult()->exists()) {
            throw ValidationException::withMessages(['game' => 'A game cannot be edited after its result has been submitted.']);
        }

        $game->update($data);
        $game->refresh();

        return $game;
    }
}
