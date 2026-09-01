<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['iso_code' => 'USA', 'iso_alpha2' => 'US', 'name' => 'United States', 'capital' => 'Washington, D.C.', 'region' => 'Americas'],
            ['iso_code' => 'GBR', 'iso_alpha2' => 'GB', 'name' => 'United Kingdom', 'capital' => 'London', 'region' => 'Europe'],
            ['iso_code' => 'NGA', 'iso_alpha2' => 'NG', 'name' => 'Nigeria', 'capital' => 'Abuja', 'region' => 'Africa'],
            ['iso_code' => 'AUS', 'iso_alpha2' => 'AU', 'name' => 'Australia', 'capital' => 'Canberra', 'region' => 'Oceania'],
            ['iso_code' => 'JAM', 'iso_alpha2' => 'JM', 'name' => 'Jamaica', 'capital' => 'Kingston', 'region' => 'Americas'],
            ['iso_code' => 'CAN', 'iso_alpha2' => 'CA', 'name' => 'Canada', 'capital' => 'Ottawa', 'region' => 'Americas'],
            ['iso_code' => 'FRA', 'iso_alpha2' => 'FR', 'name' => 'France', 'capital' => 'Paris', 'region' => 'Europe'],
            ['iso_code' => 'ESP', 'iso_alpha2' => 'ES', 'name' => 'Spain', 'capital' => 'Madrid', 'region' => 'Europe'],
            ['iso_code' => 'DEU', 'iso_alpha2' => 'DE', 'name' => 'Germany', 'capital' => 'Berlin', 'region' => 'Europe'],
            ['iso_code' => 'BRA', 'iso_alpha2' => 'BR', 'name' => 'Brazil', 'capital' => 'Brasília', 'region' => 'Americas'],
            ['iso_code' => 'ARG', 'iso_alpha2' => 'AR', 'name' => 'Argentina', 'capital' => 'Buenos Aires', 'region' => 'Americas'],
            ['iso_code' => 'ZAF', 'iso_alpha2' => 'ZA', 'name' => 'South Africa', 'capital' => 'Pretoria', 'region' => 'Africa'],
            ['iso_code' => 'KEN', 'iso_alpha2' => 'KE', 'name' => 'Kenya', 'capital' => 'Nairobi', 'region' => 'Africa'],
            ['iso_code' => 'GHA', 'iso_alpha2' => 'GH', 'name' => 'Ghana', 'capital' => 'Accra', 'region' => 'Africa'],
            ['iso_code' => 'RWA', 'iso_alpha2' => 'RW', 'name' => 'Rwanda', 'capital' => 'Kigali', 'region' => 'Africa'],
            ['iso_code' => 'UGA', 'iso_alpha2' => 'UG', 'name' => 'Uganda', 'capital' => 'Kampala', 'region' => 'Africa'],
            ['iso_code' => 'PHL', 'iso_alpha2' => 'PH', 'name' => 'Philippines', 'capital' => 'Manila', 'region' => 'Asia'],
            ['iso_code' => 'JPN', 'iso_alpha2' => 'JP', 'name' => 'Japan', 'capital' => 'Tokyo', 'region' => 'Asia'],
            ['iso_code' => 'CHN', 'iso_alpha2' => 'CN', 'name' => 'China', 'capital' => 'Beijing', 'region' => 'Asia'],
            ['iso_code' => 'IND', 'iso_alpha2' => 'IN', 'name' => 'India', 'capital' => 'New Delhi', 'region' => 'Asia'],
            ['iso_code' => 'MEX', 'iso_alpha2' => 'MX', 'name' => 'Mexico', 'capital' => 'Mexico City', 'region' => 'Americas'],
            ['iso_code' => 'ITA', 'iso_alpha2' => 'IT', 'name' => 'Italy', 'capital' => 'Rome', 'region' => 'Europe'],
            ['iso_code' => 'GRC', 'iso_alpha2' => 'GR', 'name' => 'Greece', 'capital' => 'Athens', 'region' => 'Europe'],
            ['iso_code' => 'SRB', 'iso_alpha2' => 'RS', 'name' => 'Serbia', 'capital' => 'Belgrade', 'region' => 'Europe'],
            ['iso_code' => 'LTU', 'iso_alpha2' => 'LT', 'name' => 'Lithuania', 'capital' => 'Vilnius', 'region' => 'Europe'],
            ['iso_code' => 'NZL', 'iso_alpha2' => 'NZ', 'name' => 'New Zealand', 'capital' => 'Wellington', 'region' => 'Oceania'],
            ['iso_code' => 'SEN', 'iso_alpha2' => 'SN', 'name' => 'Senegal', 'capital' => 'Dakar', 'region' => 'Africa'],
            ['iso_code' => 'CMR', 'iso_alpha2' => 'CM', 'name' => 'Cameroon', 'capital' => 'Yaoundé', 'region' => 'Africa'],
            ['iso_code' => 'EGY', 'iso_alpha2' => 'EG', 'name' => 'Egypt', 'capital' => 'Cairo', 'region' => 'Africa'],
            ['iso_code' => 'DOM', 'iso_alpha2' => 'DO', 'name' => 'Dominican Republic', 'capital' => 'Santo Domingo', 'region' => 'Americas'],
        ];

        foreach ($countries as $country) {
            Country::query()->firstOrCreate(
                ['iso_code' => $country['iso_code']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'iso_alpha2' => $country['iso_alpha2'],
                    'name' => $country['name'],
                    'capital' => $country['capital'],
                    'region' => $country['region'],
                    'flag' => 'https://flagcdn.com/'.mb_strtolower($country['iso_alpha2']).'.svg',
                ],
            );
        }
    }
}
