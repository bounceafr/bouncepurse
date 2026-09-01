<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('redirect sends user to google oauth', function (): void {
    Socialite::fake('google');

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect();
});

test('callback creates new google user as unverified and sends verification code', function (): void {
    Notification::fake();
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard'));

    /** @var User $user */
    $user = User::query()->where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole(Role::Player))->toBeTrue()
        ->and($user->social_provider)->toBe('google')
        ->and($user->social_provider_id)->toBe('google-abc123')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, EmailVerificationCodeNotification::class);
});

test('callback logs in existing verified google user without sending verification code', function (): void {
    Notification::fake();

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

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($existing);
    $response->assertRedirect(route('dashboard'));
    Notification::assertNothingSent();
});

test('callback links google account to existing email password user', function (): void {
    Notification::fake();

    $existing = User::factory()->unverified()->create([
        'email' => 'jane@example.com',
        'social_provider' => null,
        'social_provider_id' => null,
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($existing);
    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'id' => $existing->id,
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_provider_id' => 'google-abc123',
    ]);

    expect(User::query()->where('email', 'jane@example.com')->count())->toBe(1);
    Notification::assertSentTo($existing, EmailVerificationCodeNotification::class);
});

test('callback redirects to login with error when oauth is cancelled', function (): void {
    Socialite::shouldReceive('driver->user')->andThrow(new Exception('cancelled'));

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('social');
});

test('callback rejects deactivated users', function (): void {
    $existing = User::factory()->create([
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_provider_id' => 'google-abc123',
        'deactivated_at' => now(),
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('social');
});

test('verified google users with two factor enabled are redirected to two factor challenge', function (): void {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $existing = User::factory()->create([
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_provider_id' => 'google-abc123',
    ]);

    $existing->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $existing->id);
    $this->assertGuest();
});

test('unverified google users with two factor enabled still receive verification code', function (): void {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Notification::fake();

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $existing = User::factory()->unverified()->create([
        'email' => 'jane@example.com',
        'social_provider' => 'google',
        'social_provider_id' => 'google-abc123',
    ]);

    $existing->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-abc123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $this->get(route('auth.google.callback'));

    Notification::assertSentTo($existing, EmailVerificationCodeNotification::class);
});
