<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameFormat;
use App\Enums\GameParticipant;
use App\Enums\GameStatus;
use App\Enums\ResultStatus;
use App\Enums\Role;
use App\Enums\VideoStatus;
use Carbon\CarbonInterface;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read GameParticipant $participant
 * @property-read GameFormat $format
 * @property-read int $court_id
 * @property-read ?int $team_id
 * @property-read int $player_id
 * @property-read ?int $opponent_player_id
 * @property-read ?int $opponent_team_id
 * @property-read string $title
 * @property-read ?string $vimeo_uri
 * @property-read ?VideoStatus $vimeo_status
 * @property-read ?CarbonInterface $scheduled_at
 * @property-read ?CarbonInterface $played_at
 * @property-read GameStatus $status
 * @property-read ?ResultStatus $result
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read ?Court $court
 * @property-read User $player
 * @property-read ?Team $team
 * @property-read ?User $opponentPlayer
 * @property-read ?Team $opponentTeam
 * @property-read ?GameResult $gameResult
 */
#[RouteKey('uuid')]
final class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /** @return BelongsTo<Court, $this> */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /** @return BelongsTo<User, $this> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return BelongsTo<User, $this> */
    public function opponentPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_player_id');
    }

    /** @return BelongsTo<Team, $this> */
    public function opponentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'opponent_team_id');
    }

    /** @return HasOne<GameResult, $this> */
    public function gameResult(): HasOne
    {
        return $this->hasOne(GameResult::class);
    }

    /** @return HasMany<GameModeration, $this> */
    public function moderation(): HasMany
    {
        return $this->hasMany(GameModeration::class);
    }

    /** @return HasMany<Dispute, $this> */
    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function hasOpponent(): bool
    {
        return $this->participant === GameParticipant::TEAM
            ? $this->opponent_team_id !== null
            : $this->opponent_player_id !== null;
    }

    /** The creator's side: the player who created the game, or a member of their team. */
    public function isOnSubmittingSide(User $user): bool
    {
        return $this->player_id === $user->id
            || ($this->team_id !== null && $this->team?->hasMember($user) === true);
    }

    /** The opponent's side representative who must consent to a submitted result. */
    public function isConfirmer(User $user): bool
    {
        return $this->participant === GameParticipant::TEAM
            ? $this->opponentTeam?->user_id === $user->id
            : $this->opponent_player_id === $user->id;
    }

    public function isParticipant(User $user): bool
    {
        return $this->isOnSubmittingSide($user)
            || $this->opponent_player_id === $user->id
            || ($this->opponent_team_id !== null && (
                $this->opponentTeam?->user_id === $user->id
                || $this->opponentTeam?->hasMember($user) === true
            ));
    }

    /** Why a result cannot be submitted for this game right now, or null when it can. */
    public function resultSubmissionBlocker(): ?string
    {
        if (! $this->status->isOpenForResult() || $this->gameResult()->exists()) {
            return 'A result has already been submitted for this game.';
        }

        if (! $this->hasOpponent()) {
            return 'Add an opponent to this game before submitting a result.';
        }

        if ($this->played_at === null && ($this->scheduled_at === null || $this->scheduled_at->isFuture())) {
            return 'You can submit results after the scheduled time.';
        }

        return null;
    }

    /**
     * Where an answered result goes next: moderators only review games with a ready video,
     * and a rejected result goes to them as contested.
     */
    public function moderationEntryStatus(): GameStatus
    {
        if ($this->vimeo_status !== VideoStatus::Ready) {
            return GameStatus::AwaitingVideo;
        }

        return $this->gameResult?->rejected_at !== null ? GameStatus::Contested : GameStatus::Pending;
    }

    /** Release a game held for its video once that video is ready; call on a locked row. */
    public function releaseToModerationIfVideoReady(): void
    {
        if ($this->status === GameStatus::AwaitingVideo && $this->vimeo_status === VideoStatus::Ready) {
            $this->update(['status' => $this->moderationEntryStatus()]);
        }
    }

    protected static function booted(): void
    {
        self::addGlobalScope('player_scope', function (Builder $query): void {
            $user = auth()->user();

            if (! $user instanceof User || ! $user->hasRole(Role::Player->value)) {
                return;
            }

            $memberTeamIds = DB::table('team_members')->select('team_id')->where('user_id', $user->id);
            $ownedTeamIds = DB::table('teams')->select('id')->where('user_id', $user->id);

            $query->where(function (Builder $query) use ($user, $memberTeamIds, $ownedTeamIds): void {
                $query->where($query->qualifyColumn('player_id'), $user->id)
                    ->orWhere($query->qualifyColumn('opponent_player_id'), $user->id);

                foreach (['team_id', 'opponent_team_id'] as $column) {
                    $query->orWhereIn($query->qualifyColumn($column), $memberTeamIds)
                        ->orWhereIn($query->qualifyColumn($column), $ownedTeamIds);
                }
            });
        });
    }

    protected function casts(): array
    {
        return [
            'participant' => GameParticipant::class,
            'format' => GameFormat::class,
            'status' => GameStatus::class,
            'result' => ResultStatus::class,
            'vimeo_status' => VideoStatus::class,
            'scheduled_at' => 'datetime',
            'played_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
