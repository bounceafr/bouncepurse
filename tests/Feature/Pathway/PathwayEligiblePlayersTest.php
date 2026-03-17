<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Allocation;
use App\Models\Country;
use App\Models\PlayerRanking;
use App\Models\Profile;
use App\Models\RankingConfiguration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guest is redirected from pathway eligible players', function (): void {
    $this->get(route('admin.pathway-eligible.index'))
        ->assertRedirect(route('login'));
});

test('player cannot access pathway eligible players', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $this->get(route('admin.pathway-eligible.index'))
        ->assertForbidden();
});

test('admin can view pathway eligible players page', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->get(route('admin.pathway-eligible.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/pathway/eligible-players')
            ->has('candidates.data')
        );
});

test('only pathway candidate players appear', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $eligible = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $eligible->id, 'is_pathway_candidate' => true]);

    $ineligible = User::factory()->create()->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $ineligible->id, 'is_pathway_candidate' => false]);

    $this->get(route('admin.pathway-eligible.index'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('candidates.data', 1)
            ->where('candidates.data.0.id', $eligible->id)
        );
});

test('search filter works', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $player1 = User::factory()->create(['name' => 'Alice Pathway'])->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player1->id, 'is_pathway_candidate' => true]);

    $player2 = User::factory()->create(['name' => 'Bob Other'])->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player2->id, 'is_pathway_candidate' => true]);

    $this->get(route('admin.pathway-eligible.index', ['search' => 'Alice']))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('candidates.data', 1)
            ->where('candidates.data.0.name', 'Alice Pathway')
        );
});

test('csv export returns correct headers and data', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $playerNoRank = User::factory()->create(['name' => 'No Rank Player'])->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $playerNoRank->id, 'is_pathway_candidate' => true]);

    $player = User::factory()->create(['name' => 'Test Player'])->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id, 'is_pathway_candidate' => true]);

    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '1v1',
        'wins' => 5,
        'losses' => 2,
        'total_games' => 7,
        'recent_games' => 3,
        'score' => 20.5,
        'rank' => 3,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now(),
    ]);

    $response = $this->get(route('admin.pathway-eligible.export'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');

    $content = $response->getContent();
    expect($content)
        ->toContain('Name,Country,Best Rank,Approved Games,Savings Credits,Pathway Credits')
        ->toContain('"Test Player"')
        ->toContain('3');
});

test('moderator cannot access pathway eligible players', function (): void {
    $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
    $this->actingAs($moderator);

    $this->get(route('admin.pathway-eligible.index'))
        ->assertForbidden();
});

test('moderator cannot access pathway eligible players export', function (): void {
    $moderator = User::factory()->create()->assignRole(Role::Moderator->value);
    $this->actingAs($moderator);

    $this->get(route('admin.pathway-eligible.export'))
        ->assertForbidden();
});

test('guest is redirected from pathway eligible players export', function (): void {
    $this->get(route('admin.pathway-eligible.export'))
        ->assertRedirect(route('login'));
});

test('player cannot access pathway eligible players export', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $this->get(route('admin.pathway-eligible.export'))
        ->assertForbidden();
});

test('csv export shows best rank across multiple formats', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $player = User::factory()->create(['name' => 'Multi Format Player'])->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id, 'is_pathway_candidate' => true]);

    $config = RankingConfiguration::query()->create([
        'win_weight' => 3.0,
        'loss_weight' => 1.0,
        'game_count_weight' => 0.5,
        'frequency_weight' => 2.0,
    ]);

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '1v1',
        'wins' => 5,
        'losses' => 2,
        'total_games' => 7,
        'recent_games' => 3,
        'score' => 20.5,
        'rank' => 5,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now(),
    ]);

    PlayerRanking::query()->create([
        'player_id' => $player->id,
        'format' => '2v2',
        'wins' => 8,
        'losses' => 1,
        'total_games' => 9,
        'recent_games' => 5,
        'score' => 30.0,
        'rank' => 3,
        'ranking_configuration_id' => $config->id,
        'calculated_at' => now(),
    ]);

    $response = $this->get(route('admin.pathway-eligible.export'));
    $content = $response->getContent();

    $lines = explode("\n", mb_trim($content));
    $dataLine = $lines[1];

    expect($dataLine)->toContain('"Multi Format Player"')
        ->and($dataLine)->toContain(',3,');
});

test('csv export shows N/A for players with no rankings', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $player = User::factory()->create(['name' => 'Unranked Player'])->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id, 'is_pathway_candidate' => true]);

    $response = $this->get(route('admin.pathway-eligible.export'));
    $content = $response->getContent();

    expect($content)->toContain('N/A');
});

test('csv export includes allocation savings and pathway credits', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $player = User::factory()->create(['name' => 'Allocation Player'])->assignRole(Role::Player->value);
    Profile::factory()->create(['player_id' => $player->id, 'is_pathway_candidate' => true]);

    Allocation::factory()->create([
        'player_id' => $player->id,
        'savings_amount' => 10.5000,
        'pathway_amount' => 5.2500,
    ]);

    Allocation::factory()->create([
        'player_id' => $player->id,
        'savings_amount' => 4.5000,
        'pathway_amount' => 2.7500,
    ]);

    $response = $this->get(route('admin.pathway-eligible.export'));
    $content = $response->getContent();

    expect($content)
        ->toContain('"Allocation Player"')
        ->toContain('15.0000')
        ->toContain('8.0000');
});

test('csv export includes country name for players', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $country = Country::factory()->create(['name' => 'Testland']);
    $player = User::factory()->create(['name' => 'Country Player'])->assignRole(Role::Player->value);
    Profile::factory()->create([
        'player_id' => $player->id,
        'is_pathway_candidate' => true,
        'country_id' => $country->id,
    ]);

    $response = $this->get(route('admin.pathway-eligible.export'));
    $content = $response->getContent();

    expect($content)->toContain('"Country Player"')
        ->and($content)->toContain('"Testland"');
});
