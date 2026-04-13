<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function (): void {
    foreach (Role::cases() as $role) {
        Spatie\Permission\Models\Role::query()->firstOrCreate(['name' => $role->value]);
    }
});

test('redirect returns 404 for unknown provider', function (): void {
    $response = $this->get(route('auth.social.redirect', 'unknown'));

    $response->assertNotFound();
});

test('redirect sends user to google oauth', function (): void {
    Socialite::fake('google');

    $response = $this->get(route('auth.social.redirect', 'google'));

    $response->assertRedirect();
});

test('callback returns 404 for unknown provider', function (): void {
    $response = $this->get(route('auth.social.callback', 'unknown'));

    $response->assertNotFound();
});

test('callback creates new user and redirects to onboarding', function (): void {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('auth.social.callback', 'google'));

    $this->assertAuthenticated();
    $response->assertRedirect(route('onboarding.complete-profile'));

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
        'social_provider' => 'google',
        'social_provider_id' => 'google-abc123',
    ]);

    expect(User::where('email', 'jane@example.com')->first()->hasRole(Role::Player))->toBeTrue();
});

test('callback logs in existing social user and redirects to dashboard', function (): void {
    $existing = User::factory()->create([
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_provider_id' => 'google-abc123',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('auth.social.callback', 'google'));

    $this->assertAuthenticatedAs($existing);
    $response->assertRedirect(route('dashboard'));
});

test('callback links social account to existing email-password user and redirects to dashboard', function (): void {
    $existing = User::factory()->create([
        'email' => 'jane@example.com',
        'social_provider' => null,
        'social_provider_id' => null,
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('auth.social.callback', 'google'));

    $this->assertAuthenticatedAs($existing);
    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'id' => $existing->id,
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_provider_id' => 'google-abc123',
    ]);

    expect(User::query()->where('email', 'jane@example.com')->count())->toBe(1);
});

test('callback redirects to login with error when oauth is cancelled', function (): void {
    Socialite::shouldReceive('driver->user')->andThrow(new Exception('cancelled'));

    $response = $this->get(route('auth.social.callback', 'google'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});
