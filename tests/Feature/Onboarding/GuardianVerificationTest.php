<?php

declare(strict_types=1);

use App\Enums\GuardianRelationship;
use App\Enums\Role;
use App\Models\Country;
use App\Models\Guardian;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\GuardianVerificationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    foreach (Role::cases() as $role) {
        Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => $role->value]);
    }
});

test('guardian can view verification page with valid signed url', function (): void {
    $guardian = Guardian::factory()->create(['verified_at' => null]);

    $url = URL::signedRoute('onboarding.guardian-verify', ['uuid' => $guardian->uuid]);

    $response = $this->get($url);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('onboarding/guardian-verify')
        ->has('guardian')
        ->where('guardian.full_name', $guardian->full_name)
    );
});

test('guardian cannot view verification page without valid signature', function (): void {
    $guardian = Guardian::factory()->create(['verified_at' => null]);

    $response = $this->get(route('onboarding.guardian-verify', ['uuid' => $guardian->uuid]));

    $response->assertForbidden();
});

test('guardian can confirm verification with valid signed url', function (): void {
    $guardian = Guardian::factory()->create(['verified_at' => null]);

    $url = URL::signedRoute('onboarding.guardian-confirm', ['uuid' => $guardian->uuid]);

    $response = $this->post($url);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'guardian-verified');

    expect($guardian->fresh()->verified_at)->not->toBeNull();
    expect($guardian->fresh()->ip_address)->not->toBeNull();
});

test('guardian cannot confirm with invalid signature', function (): void {
    $guardian = Guardian::factory()->create(['verified_at' => null]);

    $response = $this->post(route('onboarding.guardian-confirm', ['uuid' => $guardian->uuid]));

    $response->assertForbidden();

    expect($guardian->fresh()->verified_at)->toBeNull();
});

test('verification email is sent on minor profile completion', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    $this->actingAs($user)->post(route('onboarding.store-profile'), [
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

    Notification::assertSentOnDemand(
        GuardianVerificationNotification::class,
        fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'jane@example.com'
    );
});

test('after guardian verification minor can access dashboard', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Player);

    $country = Country::factory()->create();

    Profile::factory()->create([
        'player_id' => $user->id,
        'date_of_birth' => now()->subYears(15),
        'country_id' => $country->id,
    ]);

    $guardian = Guardian::factory()->create([
        'player_id' => $user->id,
        'verified_at' => null,
    ]);

    // Before verification — redirected
    $this->actingAs($user)->get(route('dashboard'))
        ->assertRedirect(route('onboarding.guardian-pending'));

    // Verify guardian
    $url = URL::signedRoute('onboarding.guardian-confirm', ['uuid' => $guardian->uuid]);
    $this->post($url);

    // After verification — can access
    $this->actingAs($user->fresh())->get(route('dashboard'))
        ->assertOk();
});

test('resend guardian verification sends email', function (): void {
    Notification::fake();

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

    $response = $this->actingAs($user)
        ->post(route('onboarding.resend-guardian-verification'));

    $response->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentOnDemand(GuardianVerificationNotification::class);
});
