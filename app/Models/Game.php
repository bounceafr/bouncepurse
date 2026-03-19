<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameFormat;
use App\Enums\GameParticipant;
use App\Enums\GameStatus;
use App\Enums\ResultStatus;
use App\Enums\Role;
use Carbon\CarbonInterface;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read GameParticipant $participant
 * @property-read GameFormat $format
 * @property-read int $court_id
 * @property-read ?int $team_id
 * @property-read int $player_id
 * @property-read string $title
 * @property-read ?string $vimeo_uri
 * @property-read ?string $vimeo_status
 * @property-read ?CarbonInterface $scheduled_at
 * @property-read ?CarbonInterface $played_at
 * @property-read GameStatus $status
 * @property-read ?ResultStatus $result
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read ?Court $court
 * @property-read User $player
 * @property-read ?Team $team
 * @property-read ?GameResult $gameResult
 */
final class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /** @return BelongsTo<Court, Game> */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /** @return BelongsTo<User, Game> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /** @return BelongsTo<Team, Game> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return HasOne<GameResult, $this> */
    public function gameResult(): HasOne
    {
        return $this->hasOne(GameResult::class);
    }

    /** @return HasMany<GameModeration, Game> */
    public function moderation(): HasMany
    {
        return $this->hasMany(GameModeration::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function booted(): void
    {
        self::addGlobalScope('player_scope', function (Builder $query): void {
            $user = auth()->user();

            if ($user instanceof User && $user->hasRole(Role::Player->value)) {
                $query->where('player_id', $user->id);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'participant' => GameParticipant::class,
            'format' => GameFormat::class,
            'status' => GameStatus::class,
            'result' => ResultStatus::class,
            'scheduled_at' => 'datetime',
            'played_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
