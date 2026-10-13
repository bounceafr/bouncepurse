<?php

declare(strict_types=1);

use App\Enums\VideoStatus;

test('video status label returns correct string for each case', function (VideoStatus $status, string $expectedLabel): void {
    expect($status->label())->toBe($expectedLabel);
})->with([
    [VideoStatus::Uploading, 'Uploading'],
    [VideoStatus::Processing, 'Processing'],
    [VideoStatus::Ready, 'Ready'],
    [VideoStatus::Failed, 'Failed'],
]);
