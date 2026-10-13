<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResultStatus;
use Carbon\CarbonInterface;
use Database\Factories\GameResultFactory;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $game_id
 * @property-read int $submitter_id
 * @property-read ?int $responder_id
 * @property-read CarbonInterface $started_at
 * @property-read CarbonInterface $finished_at
 * @property-read int $your_score
 * @property-read int $opponent_score
 * @property-read ?CarbonInterface $confirmed_at
 * @property-read ?CarbonInterface $rejected_at
 * @property-read ?string $rejection_reason
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read Game $game
 * @property-read User $submitter
 * @property-read ?User $responder
 */
#[RouteKey('uuid')]
final class GameResult extends Model
{
    /** @use HasFactory<GameResultFactory> */
    use HasFactory;

    /** @return BelongsTo<Game, $this> */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitter_id');
    }

    /** @return BelongsTo<User, $this> */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }

    /** The outcome from the game creator's side; scores are always entered from that perspective. */
    public function outcome(): ?ResultStatus
    {
        return match ($this->your_score <=> $this->opponent_score) {
            1 => ResultStatus::WIN,
            -1 => ResultStatus::LOST,
            default => null,
        };
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
