<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\RecalculatePathwayEligibilityJob;
use App\Models\PathwayConfiguration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    PathwayConfiguration::factory()->create();
});

test('guest is redirected from pathway configuration', function (): void {
    $this->get(route('admin.pathway.edit'))
        ->assertRedirect(route('login'));
});

test('player cannot access pathway configuration', function (): void {
    $player = User::factory()->create()->assignRole(Role::Player->value);
    $this->actingAs($player);

    $this->get(route('admin.pathway.edit'))
        ->assertForbidden();
});

test('admin can view pathway configuration edit page', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->get(route('admin.pathway.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('admin/pathway/edit')
            ->has('config.min_approved_games')
            ->has('config.max_rank')
            ->has('config.max_conduct_flags')
        );
});

test('admin can update pathway configuration', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    Queue::fake();

    $this->post(route('admin.pathway.update'), [
        'min_approved_games' => 15,
        'max_rank' => 5,
        'max_conduct_flags' => 1,
    ])->assertRedirect(route('admin.pathway.edit'));

    expect(PathwayConfiguration::query()->count())->toBe(2);

    $latest = PathwayConfiguration::query()->latest('id')->first();
    expect($latest->min_approved_games)->toBe(15)
        ->and($latest->max_rank)->toBe(5)
        ->and($latest->max_conduct_flags)->toBe(1)
        ->and($latest->updated_by)->toBe($admin->id);
});

test('update dispatches recalculation job', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    Queue::fake();

    $this->post(route('admin.pathway.update'), [
        'min_approved_games' => 10,
        'max_rank' => 10,
        'max_conduct_flags' => 3,
    ]);

    Queue::assertPushed(RecalculatePathwayEligibilityJob::class);
});

test('validation rejects invalid values', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);
    $this->actingAs($admin);

    $this->post(route('admin.pathway.update'), [
        'min_approved_games' => 0,
        'max_rank' => -1,
        'max_conduct_flags' => -1,
    ])->assertSessionHasErrors(['min_approved_games', 'max_rank', 'max_conduct_flags']);

    $this->post(route('admin.pathway.update'), [
        'min_approved_games' => null,
        'max_rank' => null,
        'max_conduct_flags' => null,
    ])->assertSessionHasErrors(['min_approved_games', 'max_rank', 'max_conduct_flags']);
});
