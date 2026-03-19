<?php

declare(strict_types=1);

use App\Enums\GameStatus;
use App\Models\Court;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Permission::query()->firstOrCreate(['name' => 'view-games']);
});

test('guests are redirected from games routes', function (): void {
    $this->get(route('admin.games.index'))->assertRedirect(route('login'));
    $this->post(route('admin.games.store'))->assertRedirect(route('login'));
});

test('player can view games index', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $this->actingAs($user);

    $response = $this->get(route('admin.games.index'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/games/index')
        ->has('games')
        ->has('filters')
    );
});

test('games index includes teams data', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $this->actingAs($user);

    $response = $this->get(route('admin.games.index'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/games/index')
        ->has('courts')
        ->has('teams')
    );
});

test('player can schedule a game with valid data', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Pickup Game',
        'participant' => 'player',
        'format' => '1v1',
        'court_id' => $court->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertRedirect(route('admin.games.index'));
    $this->assertDatabaseHas('games', [
        'title' => 'Pickup Game',
        'participant' => 'player',
        'format' => '1v1',
        'court_id' => $court->id,
        'player_id' => $user->id,
        'status' => 'scheduled',
    ]);
});

test('cannot schedule a game in the past', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Past Game',
        'participant' => 'player',
        'format' => '1v1',
        'court_id' => $court->id,
        'scheduled_at' => now()->subDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertSessionHasErrors('scheduled_at');
});

test('must provide team_id when participant is team', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Team Game',
        'participant' => 'team',
        'format' => '3v3',
        'court_id' => $court->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertSessionHasErrors('team_id');
});

test('player participant can only play 1v1 format', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Bad Format',
        'participant' => 'player',
        'format' => '3v3',
        'court_id' => $court->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertSessionHasErrors('format');
});

test('team participant must use 3v3 or 5v5 format', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $court = Court::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Bad Team Format',
        'participant' => 'team',
        'format' => '1v1',
        'court_id' => $court->id,
        'team_id' => $team->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
    ]);

    $response->assertSessionHasErrors('format');
});

test('player can view a game show page', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $game = Game::factory()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->get(route('admin.games.show', $game));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/games/show')
        ->has('game')
    );
});

test('scheduled game has status scheduled and null played_at', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $court = Court::factory()->create();
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Future Game',
        'participant' => 'player',
        'format' => '1v1',
        'court_id' => $court->id,
        'scheduled_at' => now()->addWeek()->format('Y-m-d H:i:s'),
    ]);

    $this->assertDatabaseHas('games', [
        'title' => 'Future Game',
        'status' => GameStatus::Scheduled->value,
        'played_at' => null,
    ]);
});
