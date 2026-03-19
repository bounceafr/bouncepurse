<?php

declare(strict_types=1);

namespace App\Actions\Admin\Game;

use App\Enums\GameStatus;
use App\Models\Game;
use Illuminate\Support\Str;

final class StoreAction
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): Game
    {
        if (isset($data['scheduled_at'])) {
            $data['status'] = GameStatus::Scheduled;
            $data['played_at'] = null;
        }

        return Game::query()->create(array_merge($data, [
            'uuid' => Str::uuid(),
        ]));
    }
}
