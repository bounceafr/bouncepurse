<?php

declare(strict_types=1);

use App\Enums\GuardianRelationship;

test('guardian relationship label returns correct string for each case', function (GuardianRelationship $relationship, string $expectedLabel): void {
    expect($relationship->label())->toBe($expectedLabel);
})->with([
    [GuardianRelationship::MOTHER, 'Mother'],
    [GuardianRelationship::FATHER, 'Father'],
    [GuardianRelationship::BROTHER, 'Brother'],
    [GuardianRelationship::SISTER, 'Sister'],
    [GuardianRelationship::LEGAL_GUARDIAN, 'Legal Guardian'],
]);

test('guardian relationship color returns correct value for each case', function (GuardianRelationship $relationship, string $expectedColor): void {
    expect($relationship->color())->toBe($expectedColor);
})->with([
    [GuardianRelationship::MOTHER, 'green'],
    [GuardianRelationship::FATHER, 'blue'],
    [GuardianRelationship::BROTHER, 'orange'],
    [GuardianRelationship::SISTER, 'gray'],
    [GuardianRelationship::LEGAL_GUARDIAN, 'yellow'],
]);
