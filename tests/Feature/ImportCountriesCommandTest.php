<?php

declare(strict_types=1);

use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\PendingCommand;

it('imports countries on a successful response', function (): void {
    Http::fake([
        '*' => Http::response([
            [
                'cca3' => 'USA',
                'cca2' => 'US',
                'name' => ['common' => 'United States'],
                'capital' => ['Washington, D.C.'],
                'region' => 'Americas',
                'flags' => ['svg' => 'https://flagcdn.com/us.svg', 'png' => 'https://flagcdn.com/us.png'],
            ],
        ], 200),
    ]);

    $command = $this->artisan('app:import-countries-command');
    $this->assertInstanceOf(PendingCommand::class, $command);
    $command->assertSuccessful()->run();

    expect(Country::query()->where('iso_code', 'USA')->first())
        ->not->toBeNull()
        ->iso_alpha2->toBe('US')
        ->name->toBe('United States')
        ->capital->toBe('Washington, D.C.')
        ->region->toBe('Americas')
        ->flag->toBe('https://flagcdn.com/us.svg');
});

it('returns failure on an HTTP error response', function (): void {
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $command = $this->artisan('app:import-countries-command');
    $this->assertInstanceOf(PendingCommand::class, $command);
    $command->assertFailed()->run();

    expect(Country::query()->count())->toBe(0);
});

it('returns failure when the response is not an array', function (): void {
    Http::fake([
        '*' => Http::response('"not-an-array"', 200),
    ]);

    $command = $this->artisan('app:import-countries-command');
    $this->assertInstanceOf(PendingCommand::class, $command);
    $command->assertFailed()->run();

    expect(Country::query()->count())->toBe(0);
});
