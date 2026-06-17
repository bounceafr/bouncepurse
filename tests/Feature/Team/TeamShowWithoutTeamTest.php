<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('administrator without team can view team page', function (): void {
    $admin = User::factory()->create()->assignRole(Role::Administrator->value);

    $this->actingAs($admin)
        ->get(route('team.show'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('team/show')
            ->where('team', null)
            ->where('isOwner', false)
        );
});

test('moderator without team can view team page', function (): void {
    $moderator = User::factory()->create()->assignRole(Role::Moderator->value);

    $this->actingAs($moderator)
        ->get(route('team.show'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('team/show')
            ->where('team', null)
            ->where('isOwner', false)
        );
});
