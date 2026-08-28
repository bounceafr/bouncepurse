<?php

declare(strict_types=1);

use App\Enums\GameParticipant;

test('game participant label returns correct string for each case', function (GameParticipant $participant, string $expectedLabel): void {
    expect($participant->label())->toBe($expectedLabel);
})->with([
    [GameParticipant::PLAYER, 'Player'],
    [GameParticipant::TEAM, 'Team'],
]);

test('game participant color returns correct string for each case', function (GameParticipant $participant, string $expectedColor): void {
    expect($participant->color())->toBe($expectedColor);
})->with([
    [GameParticipant::PLAYER, 'orange-500'],
    [GameParticipant::TEAM, 'green-500'],
]);
