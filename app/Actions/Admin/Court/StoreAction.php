<?php

declare(strict_types=1);

namespace App\Actions\Admin\Court;

use App\Models\Country;
use App\Models\Court;
use App\Models\User;
use Illuminate\Support\Str;

final class StoreAction
{
    /** @param array<string, mixed> $data */
    public function handle(array $data, User $user): Court
    {
        $countryId = $data['country_id'];
        /** @var int|string $countryId */
        $country = Country::query()->findOrFail((int) $countryId);

        $city = $data['city'];

        /** @var string $city */
        return Court::query()->create(array_merge($data, [
            'uuid' => Str::uuid(),
            'court_code' => Court::generateCourtCode($country, $city),
            'created_by' => $user->id,
        ]));
    }
}
