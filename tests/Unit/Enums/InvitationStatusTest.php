<?php

declare(strict_types=1);

use App\Enums\InvitationStatus;

test('invitation status label returns correct string for each case', function (InvitationStatus $status, string $expectedLabel): void {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [InvitationStatus::Pending, 'Pending'],
    [InvitationStatus::Accepted, 'Accepted'],
    [InvitationStatus::Declined, 'Declined'],
    [InvitationStatus::Expired, 'Expired'],
]);

test('invitation status color returns correct value for each case', function (InvitationStatus $status, string $expectedColor): void {
    expect($status->color())->toBe($expectedColor);
})->with([
    [InvitationStatus::Pending, 'bg-yellow-500'],
    [InvitationStatus::Accepted, 'bg-green-500'],
    [InvitationStatus::Declined, 'bg-red-500'],
    [InvitationStatus::Expired, 'bg-gray-500'],
]);
