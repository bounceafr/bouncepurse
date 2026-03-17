<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GuardianRelationship;
use Carbon\CarbonInterface;
use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read int $player_id
 * @property-read string $full_name
 * @property-read string $email
 * @property-read string $phone
 * @property-read string $address
 * @property-read GuardianRelationship $relationship
 * @property-read ?CarbonInterface $verified_at
 * @property-read ?string $ip_address
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read User $player
 */
final class Guardian extends Model
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @return BelongsTo<User, self> */
    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'relationship' => GuardianRelationship::class,
            'verified_at' => 'datetime',
        ];
    }
}
