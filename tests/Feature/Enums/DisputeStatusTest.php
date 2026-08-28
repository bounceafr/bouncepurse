<?php

declare(strict_types=1);

use App\Enums\DisputeStatus;

test('dispute status label returns correct string for each case', function (DisputeStatus $status, string $expectedLabel): void {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [DisputeStatus::Pending, 'Pending'],
    [DisputeStatus::Resolved, 'Resolved'],
    [DisputeStatus::Dismissed, 'Dismissed'],
]);
