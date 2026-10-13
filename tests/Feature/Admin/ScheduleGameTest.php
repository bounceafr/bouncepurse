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
    Permission::query()->firstOrCreate(['name' => 'create-games']);
});

test('guests are redirected from games routes', function (): void {
    $this->get(route('admin.games.index'))->assertRedirect(route('login'));
    $this->post(route('admin.games.store'))->assertRedirect(route('login'));
});

test('player can view games index', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
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
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
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
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $opponent = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Pickup Game',
        'participant' => 'player',
        'opponent_player_id' => $opponent->id,
        'format' => '1v1',
        'court_id' => $court->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ]);

    $response->assertRedirect(route('admin.games.index'));
    $this->assertDatabaseHas('games', [
        'title' => 'Pickup Game',
        'participant' => 'player',
        'opponent_player_id' => $opponent->id,
        'format' => '1v1',
        'court_id' => $court->id,
        'player_id' => $user->id,
        'status' => 'scheduled',
    ]);
});

test('cannot schedule a game in the past', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Past Game',
        'participant' => 'player',
        'format' => '1v1',
        'court_id' => $court->id,
        'scheduled_at' => now()->subDay()->toAtomString(),
    ]);

    $response->assertSessionHasErrors('scheduled_at');
});

test('must provide team_id when participant is team', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Team Game',
        'participant' => 'team',
        'format' => '3v3',
        'court_id' => $court->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ]);

    $response->assertSessionHasErrors('team_id');
});

test('player participant can only play 1v1 format', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Bad Format',
        'participant' => 'player',
        'format' => '3v3',
        'court_id' => $court->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ]);

    $response->assertSessionHasErrors('format');
});

test('team participant must use 3v3 or 5v5 format', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Bad Team Format',
        'participant' => 'team',
        'format' => '1v1',
        'court_id' => $court->id,
        'team_id' => $team->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ]);

    $response->assertSessionHasErrors('format');
});

test('player can view a game show page', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $game = Game::factory()->create(['player_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->get(route('admin.games.show', $game));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->component('admin/games/show')
        ->has('game')
    );
});

test('cannot create game with future played_at', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'Future Played Game',
        'participant' => 'player',
        'format' => '1v1',
        'court_id' => $court->id,
        'played_at' => now()->addDay()->toAtomString(),
    ]);

    $response->assertSessionHasErrors('played_at');
});

test('scheduled game has status scheduled and null played_at', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Future Game',
        'participant' => 'player',
        'opponent_player_id' => User::factory()->create()->id,
        'format' => '1v1',
        'court_id' => $court->id,
        'scheduled_at' => now()->addWeek()->toAtomString(),
    ]);

    $this->assertDatabaseHas('games', [
        'title' => 'Future Game',
        'status' => GameStatus::Scheduled->value,
        'played_at' => null,
    ]);
});

test('played_at game has status awaiting result', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Already Played Game',
        'participant' => 'player',
        'opponent_player_id' => User::factory()->create()->id,
        'format' => '1v1',
        'court_id' => $court->id,
        'played_at' => now()->subDay()->toAtomString(),
    ]);

    $this->assertDatabaseHas('games', [
        'title' => 'Already Played Game',
        'status' => GameStatus::AwaitingResult->value,
    ]);
});

test('cannot create game without scheduled_at or played_at', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $court = Court::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('admin.games.store'), [
        'title' => 'No Date Game',
        'participant' => 'player',
        'format' => '1v1',
        'court_id' => $court->id,
    ]);

    $response->assertSessionHasErrors('scheduled_at');
});

test('cannot create a game without an opponent', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Solo Game',
        'participant' => 'player',
        'format' => '1v1',
        'scheduled_at' => now()->addDay()->toAtomString(),
    ])->assertSessionHasErrors('opponent_player_id');
});

test('cannot create a game against yourself', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Mirror Match',
        'participant' => 'player',
        'format' => '1v1',
        'opponent_player_id' => $user->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ])->assertSessionHasErrors('opponent_player_id');
});

test('cannot create a game for a team you do not belong to', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $strangersTeam = Team::factory()->create();
    $opponentTeam = Team::factory()->create();
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Borrowed Team',
        'participant' => 'team',
        'format' => '5v5',
        'team_id' => $strangersTeam->id,
        'opponent_team_id' => $opponentTeam->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ])->assertSessionHasErrors('team_id');
});

test('team member can create a game against another team', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $team = Team::factory()->create(['user_id' => $user->id]);
    $team->members()->attach($user->id, ['joined_at' => now()]);
    $opponentTeam = Team::factory()->create();
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Derby',
        'participant' => 'team',
        'format' => '5v5',
        'team_id' => $team->id,
        'opponent_team_id' => $opponentTeam->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ])->assertRedirect(route('admin.games.index'));

    $this->assertDatabaseHas('games', [
        'title' => 'Derby',
        'team_id' => $team->id,
        'opponent_team_id' => $opponentTeam->id,
    ]);
});

test('users without create-games permission cannot create games', function (): void {
    $user = User::factory()->create()->givePermissionTo('view-games');
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Sneaky Game',
        'participant' => 'player',
        'format' => '1v1',
        'opponent_player_id' => User::factory()->create()->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ])->assertForbidden();
});

test('client supplied result is ignored when creating a game', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Self Reported Win',
        'participant' => 'player',
        'format' => '1v1',
        'opponent_player_id' => User::factory()->create()->id,
        'played_at' => now()->subDay()->toAtomString(),
        'result' => 'win',
    ]);

    $this->assertDatabaseHas('games', ['title' => 'Self Reported Win', 'result' => null]);
});

test('the opposing team cannot be one of your own teams', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $team = Team::factory()->create(['user_id' => $user->id]);
    $otherOwnTeam = Team::factory()->create(['user_id' => $user->id]);
    $team->members()->attach($user->id, ['joined_at' => now()]);
    $otherOwnTeam->members()->attach($user->id, ['joined_at' => now()]);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Scrimmage With Myself',
        'participant' => 'team',
        'format' => '5v5',
        'team_id' => $team->id,
        'opponent_team_id' => $otherOwnTeam->id,
        'scheduled_at' => now()->addDay()->toAtomString(),
    ])->assertSessionHasErrors('opponent_team_id');
});

test('scheduled_at with an offset is stored in UTC', function (): void {
    $this->travelTo('2026-10-13 10:00:00');
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Evening Game',
        'participant' => 'player',
        'opponent_player_id' => User::factory()->create()->id,
        'format' => '1v1',
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => '2026-10-13T18:30:00+02:00',
    ])->assertSessionHasNoErrors();

    expect(Game::query()->where('title', 'Evening Game')->sole()->scheduled_at?->toDateTimeString())
        ->toBe('2026-10-13 16:30:00');
});

test('a UTC+2 player cannot schedule a game that is already past locally', function (): void {
    $this->travelTo('2026-10-13 10:00:00');
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    // 11:30 in UTC+2 is 09:30 UTC, half an hour ago, though it reads as the future in UTC.
    $this->post(route('admin.games.store'), [
        'title' => 'Too Late',
        'participant' => 'player',
        'opponent_player_id' => User::factory()->create()->id,
        'format' => '1v1',
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => '2026-10-13T11:30:00+02:00',
    ])->assertSessionHasErrors('scheduled_at');
});

test('scheduled_at without a timezone offset is rejected', function (): void {
    $user = User::factory()->create()->givePermissionTo(['view-games', 'create-games']);
    $this->actingAs($user);

    $this->post(route('admin.games.store'), [
        'title' => 'Ambiguous',
        'participant' => 'player',
        'opponent_player_id' => User::factory()->create()->id,
        'format' => '1v1',
        'court_id' => Court::factory()->create()->id,
        'scheduled_at' => now()->addDay()->format('Y-m-d H:i'),
    ])->assertSessionHasErrors(['scheduled_at' => 'The scheduled time must include a timezone offset.']);
});
