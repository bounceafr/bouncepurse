<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $min_approved_games
 * @property-read int $max_rank
 * @property-read int $max_conduct_flags
 * @property-read ?int $updated_by
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read ?User $updatedBy
 */
final class PathwayConfiguration extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /** @return BelongsTo<User,self> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'min_approved_games' => 'integer',
            'max_rank' => 'integer',
            'max_conduct_flags' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
