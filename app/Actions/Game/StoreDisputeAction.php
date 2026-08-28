<?php

declare(strict_types=1);

namespace App\Actions\Game;

use App\Enums\GameStatus;
use App\Models\Dispute;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class StoreDisputeAction
{
    public function handle(Game $game, User $player, string $reason): Dispute
    {
        throw_if($game->status !== GameStatus::Flagged, HttpException::class, 403, 'Disputes can only be submitted for flagged games.');

        throw_if($game->gameResult?->submitter_id !== $player->id, HttpException::class, 403, 'Only the player who submitted the result may dispute this game.');

        $existing = $game->disputes()->where('player_id', $player->id)->first();

        if ($existing instanceof Dispute) {
            return $existing;
        }

        return $game->disputes()->create([
            'uuid' => Str::uuid()->toString(),
            'player_id' => $player->id,
            'reason' => $reason,
        ]);
    }
}
