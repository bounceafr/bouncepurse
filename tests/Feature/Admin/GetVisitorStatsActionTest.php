<?php

declare(strict_types=1);

use App\Actions\Admin\GetVisitorStatsAction;
use Illuminate\Support\Facades\DB;

test('returns an entry for each day in the range', function (): void {
    $action = new GetVisitorStatsAction;
    $result = $action->handle(7);

    expect($result)->toHaveCount(7);
    expect($result[0])->toHaveKeys(['date', 'desktop', 'mobile']);
});

test('counts desktop sessions correctly', function (): void {
    DB::table('sessions')->insert([
        'id' => 'test-desktop',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0',
        'last_activity' => now()->timestamp,
        'payload' => '',
    ]);

    $action = new GetVisitorStatsAction;
    $result = $action->handle(1);
    $today = $result[0];

    expect($today['desktop'])->toBeGreaterThanOrEqual(1);
    expect($today['mobile'])->toBe(0);
});

test('counts mobile sessions correctly', function (): void {
    DB::table('sessions')->insert([
        'id' => 'test-mobile',
        'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148',
        'last_activity' => now()->timestamp,
        'payload' => '',
    ]);

    $action = new GetVisitorStatsAction;
    $result = $action->handle(1);
    $today = $result[0];

    expect($today['mobile'])->toBeGreaterThanOrEqual(1);
});

test('excludes sessions older than the day range', function (): void {
    DB::table('sessions')->insert([
        'id' => 'test-old',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'last_activity' => now()->subDays(10)->timestamp,
        'payload' => '',
    ]);

    $action = new GetVisitorStatsAction;
    $result = $action->handle(7);

    $total = array_sum(array_map(fn ($r) => $r['desktop'] + $r['mobile'], $result));
    expect($total)->toBe(0);
});
