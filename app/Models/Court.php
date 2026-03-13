<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CourtStatus;
use Carbon\CarbonInterface;
use Database\Factories\CourtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $uuid
 * @property-read string $court_code
 * @property-read int $country_id
 * @property-read string $name
 * @property-read string $city
 * @property-read Country $country
 * @property-read ?string $host_name
 * @property-read ?string $contact_email
 * @property-read ?string $contact_phone
 * @property-read ?float $latitude
 * @property-read ?float $longitude
 * @property-read CourtStatus $status
 * @property-read int $created_by
 * @property-read ?CarbonInterface $created_at
 * @property-read ?CarbonInterface $updated_at
 * @property-read User $createdBy
 */
final class Court extends Model
{
    /** @use HasFactory<CourtFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function generateCourtCode(Country $country, string $city): string
    {
        $countryCode = mb_strtoupper($country->iso_alpha2);
        $cityCode = mb_strtoupper(mb_substr($city, 0, 3));
        $prefix = sprintf('%s-%s', $countryCode, $cityCode);

        $sequence = self::query()->count() + 1;

        return sprintf('%s-%06d', $prefix, $sequence);
    }

    /** @return BelongsTo<Country, self> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<User, self> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'status' => CourtStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
