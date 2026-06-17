<?php

declare(strict_types=1);

use App\Enums\TeamStatus;

test('team status label returns correct string for each case', function (TeamStatus $status, string $expectedLabel): void {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [TeamStatus::PENDING, 'Pending'],
    [TeamStatus::ACTIVE, 'Active'],
]);

test('team status color returns correct value for each case', function (TeamStatus $status, string $expectedColor): void {
    expect($status->color())->toBe($expectedColor);
})->with([
    [TeamStatus::PENDING, 'orange'],
    [TeamStatus::ACTIVE, 'green'],
]);
