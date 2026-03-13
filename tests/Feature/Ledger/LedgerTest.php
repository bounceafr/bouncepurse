<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Allocation;
use App\Models\AllocationConfiguration;
use App\Models\Game;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    AllocationConfiguration::query()->create([
        'insurance_percentage' => 25.0,
        'savings_percentage' => 25.0,
        'pathway_percentage' => 25.0,
        'administration_percentage' => 25.0,
    ]);
});

test('guest is redirected from ledger', function (): void {
    $this->get(route('ledger'))
        ->assertRedirect(route('login'));
});

test('player can view ledger', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id]);
    $this->actingAs($player);

    $this->get(route('ledger'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('ledger/index')
            ->has('summary.total')
            ->has('summary.savings')
            ->has('summary.pathway')
            ->has('total_games')
            ->has('approved_games')
        );
});

test('player only sees own allocations', function (): void {
    $player1 = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player1->id]);
    $player2 = User::factory()->create()->assignRole(Role::Player->value);

    $config = AllocationConfiguration::query()->latest()->first();

    $game1 = Game::factory()->create(['player_id' => $player1->id, 'status' => GameStatus::Approved]);
    $game2 = Game::factory()->create(['player_id' => $player2->id, 'status' => GameStatus::Approved]);

    foreach ([[$game1, $player1], [$game2, $player2]] as [$game, $player]) {
        Allocation::query()->create([
            'game_id' => $game->id,
            'player_id' => $player->id,
            'total_amount' => 1.00,
            'insurance_amount' => 0.25,
            'savings_amount' => 0.25,
            'pathway_amount' => 0.25,
            'administration_amount' => 0.25,
            'allocation_configuration_id' => $config->id,
        ]);
    }

    $this->actingAs($player1);

    $this->get(route('ledger'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('summary.count', 1)
        );
});

test('ledger filters by date range and format', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id]);
    $config = AllocationConfiguration::query()->latest()->first();

    $game = Game::factory()->create(['player_id' => $player->id, 'format' => '1v1']);

    Allocation::query()->create([
        'game_id' => $game->id,
        'player_id' => $player->id,
        'total_amount' => 1.00,
        'insurance_amount' => 0.25,
        'savings_amount' => 0.25,
        'pathway_amount' => 0.25,
        'administration_amount' => 0.25,
        'allocation_configuration_id' => $config->id,
        'created_at' => now()->subDays(10),
    ]);

    $this->actingAs($player);

    $this->get(route('ledger', ['from' => now()->subDays(15)->toDateString(), 'to' => now()->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('summary.count', 1)
        );

    $this->get(route('ledger', ['from' => now()->subDays(5)->toDateString()]))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('summary.count', 0)
        );

    $this->get(route('ledger', ['format' => '2v2']))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('summary.count', 0)
        );
});

test('summary stats are correct for player', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id]);
    $config = AllocationConfiguration::query()->latest()->first();

    Game::factory()->count(3)->create(['player_id' => $player->id, 'status' => GameStatus::Approved]);
    Game::factory()->count(2)->create(['player_id' => $player->id, 'status' => GameStatus::Pending]);

    $approvedGames = Game::query()->withoutGlobalScopes()->where('player_id', $player->id)->where('status', GameStatus::Approved)->get();
    foreach ($approvedGames as $game) {
        Allocation::query()->create([
            'game_id' => $game->id,
            'player_id' => $player->id,
            'total_amount' => 1.00,
            'insurance_amount' => 0.25,
            'savings_amount' => 0.25,
            'pathway_amount' => 0.25,
            'administration_amount' => 0.25,
            'allocation_configuration_id' => $config->id,
        ]);
    }

    $this->actingAs($player);

    $this->get(route('ledger'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('total_games', 5)
            ->where('approved_games', 3)
            ->where('summary.count', 3)
        );
});
