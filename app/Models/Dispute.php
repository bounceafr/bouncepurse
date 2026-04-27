<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $game_id
 * @property-read int $player_id
 * @property-read string $reason
 * @property-read DisputeStatus $status
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read Game $game
 * @property-read User $player
 */
final class Dispute extends Model
{
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /** @return BelongsTo<Game, self> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<User, self> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    protected function casts(): array
    {
        return [
            'status' => DisputeStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
