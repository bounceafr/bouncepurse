<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\AllocationConfiguration;
use App\Models\Game;
use App\Models\User;

test('allocation belongs to a game', function (): void {
    $allocation = Allocation::factory()->create();

    expect($allocation->game)->toBeInstanceOf(Game::class)
        ->and($allocation->game->id)->toBe($allocation->game_id);
});

test('allocation belongs to a player', function (): void {
    $allocation = Allocation::factory()->create();

    expect($allocation->player)->toBeInstanceOf(User::class)
        ->and($allocation->player->id)->toBe($allocation->player_id);
});

test('allocation belongs to an allocation configuration', function (): void {
    $allocation = Allocation::factory()->create();

    expect($allocation->allocationConfiguration)->toBeInstanceOf(AllocationConfiguration::class)
        ->and($allocation->allocationConfiguration->id)->toBe($allocation->allocation_configuration_id);
});

test('allocation casts amount fields to float', function (): void {
    $allocation = Allocation::factory()->create([
        'total_amount' => 1.0,
        'insurance_amount' => 0.25,
        'savings_amount' => 0.25,
        'pathway_amount' => 0.25,
        'administration_amount' => 0.25,
    ]);

    expect($allocation->total_amount)->toBeFloat()
        ->and($allocation->insurance_amount)->toBeFloat()
        ->and($allocation->savings_amount)->toBeFloat()
        ->and($allocation->pathway_amount)->toBeFloat()
        ->and($allocation->administration_amount)->toBeFloat();
});

test('allocation configuration has many allocations', function (): void {
    $config = AllocationConfiguration::factory()->create();

    Allocation::factory()->count(3)->create(['allocation_configuration_id' => $config->id]);

    expect($config->allocations)->toHaveCount(3)
        ->each->toBeInstanceOf(Allocation::class);
});

test('allocation configuration belongs to updated by user', function (): void {
    $user = User::factory()->create();
    $config = AllocationConfiguration::factory()->create(['updated_by' => $user->id]);

    expect($config->updatedBy)->toBeInstanceOf(User::class)
        ->and($config->updatedBy->id)->toBe($user->id);
});

test('allocation configuration updated by is nullable', function (): void {
    $config = AllocationConfiguration::factory()->create(['updated_by' => null]);

    expect($config->updatedBy)->toBeNull();
});

test('allocation configuration casts percentage fields to float', function (): void {
    $config = AllocationConfiguration::factory()->create();

    expect($config->insurance_percentage)->toBeFloat()
        ->and($config->savings_percentage)->toBeFloat()
        ->and($config->pathway_percentage)->toBeFloat()
        ->and($config->administration_percentage)->toBeFloat();
});
