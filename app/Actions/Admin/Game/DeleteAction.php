<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Models\Game;
use Illuminate\Validation\ValidationException;

final class DeleteAction
{
    public function handle(Game $game): void
    {
        if ($game->gameResult()->exists()) {
            throw ValidationException::withMessages(['game' => 'A game cannot be deleted after its result has been submitted.']);
        }

        $game->delete();
    }
}
