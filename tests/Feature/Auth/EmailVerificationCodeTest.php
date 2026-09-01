<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('google users see code verification screen', function (): void {
    $user = User::factory()->unverified()->create([
        'social_provider' => 'google',
        'social_provider_id' => 'google-123',
    ]);

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('auth/verify-email')
        ->where('verificationMethod', 'code')
    );
});

test('email password users see link verification screen', function (): void {
    $user = User::factory()->unverified()->create([
        'social_provider' => null,
        'social_provider_id' => null,
    ]);

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page): Assert => $page
        ->component('auth/verify-email')
        ->where('verificationMethod', 'link')
    );
});

test('valid verification code verifies google user email', function (): void {
    Event::fake([Verified::class]);
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'social_provider' => 'google',
        'social_provider_id' => 'google-123',
    ]);

    $code = null;

    $this->actingAs($user)->post(route('verification.code.resend'));

    Notification::assertSentTo(
        $user,
        EmailVerificationCodeNotification::class,
        function (EmailVerificationCodeNotification $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        },
    );

    $response = $this->actingAs($user)->post(route('verification.code.verify'), [
        'code' => $code,
    ]);

    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('invalid verification code is rejected', function (): void {
    $user = User::factory()->unverified()->create([
        'social_provider' => 'google',
        'social_provider_id' => 'google-123',
    ]);

    $this->actingAs($user)
        ->post(route('verification.code.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('email password users cannot verify with code endpoint', function (): void {
    $user = User::factory()->unverified()->create([
        'social_provider' => null,
        'social_provider_id' => null,
    ]);

    $this->actingAs($user)
        ->post(route('verification.code.verify'), ['code' => '123456'])
        ->assertForbidden();
});

test('resend verification code sends notification to google user', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'social_provider' => 'google',
        'social_provider_id' => 'google-123',
    ]);

    $this->actingAs($user)
        ->post(route('verification.code.resend'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-code-sent');

    Notification::assertSentTo($user, EmailVerificationCodeNotification::class);
});
