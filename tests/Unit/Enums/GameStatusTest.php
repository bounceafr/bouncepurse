<?php

declare(strict_types=1);

use App\Enums\GameStatus;

test('game status label returns correct string for each case', function (GameStatus $status, string $expectedLabel): void {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [GameStatus::Scheduled, 'Scheduled'],
    [GameStatus::Pending, 'Pending'],
    [GameStatus::Approved, 'Approved'],
    [GameStatus::Rejected, 'Rejected'],
    [GameStatus::Flagged, 'Flagged'],
]);

test('game status color returns correct class for each case', function (GameStatus $status, string $expectedColor): void {
    expect($status->color())->toBe($expectedColor);
})->with([
    [GameStatus::Scheduled, 'blue-500'],
    [GameStatus::Pending, 'yellow-500'],
    [GameStatus::Approved, 'green-500'],
    [GameStatus::Rejected, 'red-500'],
    [GameStatus::Flagged, 'orange-500'],
]);
