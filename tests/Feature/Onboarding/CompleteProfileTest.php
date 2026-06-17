<?php

declare(strict_types=1);

use App\Enums\GuardianRelationship;
use App\Enums\Role;
use App\Models\Country;
use App\Models\Guardian;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\GuardianVerificationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    foreach (Role::cases() as $role) {
        Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => $role->value]);
    }
});

test('player without profile is redirected to onboarding', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('onboarding.complete-profile'));
});

test('player can view complete profile page', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $response = $this->actingAs($user)->get(route('onboarding.complete-profile'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('onboarding/complete-profile')
        ->has('countries')
        ->has('guardianRelationships')
    );
});

test('adult player can complete profile and is redirected to dashboard', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('onboarding.store-profile'), [
        'date_of_birth' => '1995-06-15',
        'country_id' => $country->id,
        'city' => 'Lagos',
        'phone_number' => '+2348012345678',
        'bio' => 'Basketball player.',
        'position' => 'Point Guard',
    ]);

    $response->assertRedirect(route('dashboard'));

    expect($user->profile()->exists())->toBeTrue();
});

test('minor player requires guardian fields', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('onboarding.store-profile'), [
        'date_of_birth' => now()->subYears(15)->format('Y-m-d'),
        'country_id' => $country->id,
        'city' => 'Lagos',
        'phone_number' => '+2348012345678',
        'bio' => 'Young player.',
        'position' => 'Center',
    ]);

    $response->assertSessionHasErrors([
        'guardian.full_name',
        'guardian.email',
        'guardian.phone',
        'guardian.address',
        'guardian.relationship',
    ]);
});

test('minor player with guardian data is redirected to guardian-pending', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('onboarding.store-profile'), [
        'date_of_birth' => now()->subYears(15)->format('Y-m-d'),
        'country_id' => $country->id,
        'city' => 'Lagos',
        'phone_number' => '+2348012345678',
        'bio' => 'Young player.',
        'position' => 'Center',
        'guardian' => [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+2348099999999',
            'address' => '123 Main St',
            'relationship' => GuardianRelationship::MOTHER->value,
        ],
    ]);

    $response->assertRedirect(route('onboarding.guardian-pending'));

    expect($user->profile()->exists())->toBeTrue();
    expect($user->guardian()->exists())->toBeTrue();

    Notification::assertSentOnDemand(GuardianVerificationNotification::class);
});

test('adult player does not require guardian fields', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('onboarding.store-profile'), [
        'date_of_birth' => '1990-01-01',
        'country_id' => $country->id,
        'city' => 'Lagos',
        'phone_number' => '+2348012345678',
        'bio' => 'Player bio.',
        'position' => 'Point Guard',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dashboard'));
});

test('validation errors for missing required profile fields', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $response = $this->actingAs($user)->post(route('onboarding.store-profile'), []);

    $response->assertSessionHasErrors([
        'date_of_birth',
        'country_id',
        'city',
        'phone_number',
        'bio',
        'position',
    ]);
});

test('minor with unverified guardian is redirected to guardian-pending', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    Profile::factory()->create([
        'player_id' => $user->id,
        'date_of_birth' => now()->subYears(15),
        'country_id' => $country->id,
    ]);

    Guardian::factory()->create([
        'player_id' => $user->id,
        'verified_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('onboarding.guardian-pending'));
});

test('minor with verified guardian can access dashboard', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    Profile::factory()->create([
        'player_id' => $user->id,
        'date_of_birth' => now()->subYears(15),
        'country_id' => $country->id,
    ]);

    Guardian::factory()->verified()->create([
        'player_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('adult player can complete profile with profile image', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $response = $this->actingAs($user)->post(route('onboarding.store-profile'), [
        'date_of_birth' => '1995-06-15',
        'country_id' => $country->id,
        'city' => 'Lagos',
        'phone_number' => '+2348012345678',
        'bio' => 'Basketball player.',
        'position' => 'Point Guard',
        'profile_image' => UploadedFile::fake()->image('avatar.jpg'),
    ]);

    $response->assertRedirect(route('dashboard'));

    expect($user->fresh()->profile->profile_image)->not->toBeNull();
    Storage::disk('public')->assertExists($user->fresh()->profile->profile_image);
});

test('guardian pending redirects to complete profile when no guardian exists', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    Profile::factory()->create([
        'player_id' => $user->id,
        'date_of_birth' => now()->subYears(15),
    ]);

    $response = $this->actingAs($user)->get(route('onboarding.guardian-pending'));

    $response->assertRedirect(route('onboarding.complete-profile'));
});

test('guardian pending redirects to dashboard when guardian is verified', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    Profile::factory()->create([
        'player_id' => $user->id,
        'date_of_birth' => now()->subYears(15),
    ]);

    Guardian::factory()->verified()->create(['player_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('onboarding.guardian-pending'));

    $response->assertRedirect(route('dashboard'));
});

test('guardian pending page shows masked email', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    Profile::factory()->create([
        'player_id' => $user->id,
        'date_of_birth' => now()->subYears(15),
        'country_id' => $country->id,
    ]);

    Guardian::factory()->create([
        'player_id' => $user->id,
        'email' => 'guardian@example.com',
        'verified_at' => null,
    ]);

    $response = $this->actingAs($user)->get(route('onboarding.guardian-pending'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('onboarding/guardian-pending')
        ->where('guardianEmail', 'gu******@example.com')
    );
});
