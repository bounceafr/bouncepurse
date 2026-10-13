<?php

declare(strict_types=1);

use App\Enums\GameStatus;

test('game status label returns correct string for each case', function (GameStatus $status, string $expectedLabel): void {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [GameStatus::Scheduled, 'Scheduled'],
    [GameStatus::AwaitingResult, 'Awaiting Result'],
    [GameStatus::AwaitingVideo, 'Awaiting Video'],
    [GameStatus::AwaitingConfirmation, 'Awaiting Confirmation'],
    [GameStatus::Contested, 'Contested'],
    [GameStatus::Pending, 'Pending'],
    [GameStatus::Approved, 'Approved'],
    [GameStatus::Rejected, 'Rejected'],
    [GameStatus::Flagged, 'Flagged'],
]);

test('game status color returns correct class for each case', function (GameStatus $status, string $expectedColor): void {
    expect($status->color())->toBe($expectedColor);
})->with([
    [GameStatus::Scheduled, 'blue-500'],
    [GameStatus::AwaitingResult, 'sky-500'],
    [GameStatus::AwaitingVideo, 'indigo-500'],
    [GameStatus::AwaitingConfirmation, 'purple-500'],
    [GameStatus::Contested, 'orange-600'],
    [GameStatus::Pending, 'yellow-500'],
    [GameStatus::Approved, 'green-500'],
    [GameStatus::Rejected, 'red-500'],
    [GameStatus::Flagged, 'orange-500'],
]);

test('only scheduled and awaiting result games are open for a result', function (GameStatus $status, bool $open): void {
    expect($status->isOpenForResult())->toBe($open);
})->with([
    [GameStatus::Scheduled, true],
    [GameStatus::AwaitingResult, true],
    [GameStatus::AwaitingConfirmation, false],
    [GameStatus::AwaitingVideo, false],
    [GameStatus::Pending, false],
    [GameStatus::Approved, false],
]);

test('approved, rejected and flagged games are reviewed', function (GameStatus $status, bool $reviewed): void {
    expect($status->isReviewed())->toBe($reviewed);
})->with([
    [GameStatus::Approved, true],
    [GameStatus::Rejected, true],
    [GameStatus::Flagged, true],
    [GameStatus::Pending, false],
    [GameStatus::Contested, false],
    [GameStatus::AwaitingVideo, false],
]);
