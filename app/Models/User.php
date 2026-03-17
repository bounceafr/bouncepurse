<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read string $name
 * @property-read string $email
 * @property ?CarbonInterface $email_verified_at
 * @property-read ?string $two_factor_secret
 * @property-read ?string $two_factor_recovery_codes
 * @property-read ?CarbonInterface $two_factor_confirmed_at
 * @property-read ?string $remember_token
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property ?CarbonInterface $deactivated_at
 * @property ?int $deactivated_by
 * @property ?string $deactivation_reason
 * @property-read ?Profile $profile
 * @property-read Collection<int, PlayerRanking> $rankings
 * @property-read Collection<int, Game> $games
 * @property-read Collection<int, GameModeration> $moderationReviews
 * @property-read ?Guardian $guardian
 * @property-read ?Team $ownedTeam
 * @property-read Collection<int, Team> $teams
 * @property-read ?User $deactivatedBy
 */
final class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return HasOne<Profile, User> */
    public function profile(): HasOne
    {
        // @phpstan-ignore-next-line return.type
        return $this->hasOne(Profile::class, 'player_id');
    }

    /** @return HasMany<PlayerRanking,self> */
    public function rankings(): HasMany
    {
        return $this->hasMany(PlayerRanking::class, 'player_id');
    }

    /** @return HasMany<Game, self> */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'player_id');
    }

    /** @return HasMany<GameModeration, self> */
    public function moderationReviews(): HasMany
    {
        return $this->hasMany(GameModeration::class, 'moderator_id');
    }

    /** @return HasOne<Guardian, User> */
    public function guardian(): HasOne
    {
        // @phpstan-ignore-next-line return.type
        return $this->hasOne(Guardian::class, 'player_id');
    }

    public function isMinor(): bool
    {
        return $this->profile !== null && $this->profile->date_of_birth->age < 18;
    }

    /** @return HasOne<Team, User> */
    public function ownedTeam(): HasOne
    {
        // @phpstan-ignore-next-line return.type
        return $this->hasOne(Team::class);
    }

    /** @return BelongsToMany<Team, self> */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /** @return BelongsTo<User, self> */
    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deactivated_by');
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /** @param Builder<User> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }
}
