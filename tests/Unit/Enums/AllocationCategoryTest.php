<?php

declare(strict_types=1);

use App\Enums\AllocationCategory;

test('allocation category label returns correct string for each case', function (AllocationCategory $category, string $expectedLabel): void {
    expect($category->label())->toBe($expectedLabel);
})->with([
    [AllocationCategory::Insurance, 'Insurance'],
    [AllocationCategory::Savings, 'Savings'],
    [AllocationCategory::Pathway, 'Pathway'],
    [AllocationCategory::Administration, 'Administration'],
]);

test('allocation category has correct backing value for each case', function (AllocationCategory $category, string $expectedValue): void {
    expect($category->value)->toBe($expectedValue);
})->with([
    [AllocationCategory::Insurance, 'insurance'],
    [AllocationCategory::Savings, 'savings'],
    [AllocationCategory::Pathway, 'pathway'],
    [AllocationCategory::Administration, 'administration'],
]);
