<?php

declare(strict_types=1);

use App\Enums\GameFormat;

test('game format label returns correct string for each case', function (GameFormat $format, string $expectedLabel): void {
    expect($format->label())->toBe($expectedLabel);
})->with([
    [GameFormat::ONE_ON_ONE, 'One On One'],
    [GameFormat::THREE_ON_THREE, 'Three On Three'],
    [GameFormat::FIVE_ON_FIVE, 'Five On Five'],
]);

test('game format colors returns correct string for each case', function (GameFormat $format, string $expectedColor): void {
    expect($format->colors())->toBe($expectedColor);
})->with([
    [GameFormat::ONE_ON_ONE, 'green-600'],
    [GameFormat::THREE_ON_THREE, 'orange-600'],
    [GameFormat::FIVE_ON_FIVE, 'gray-600'],
]);
