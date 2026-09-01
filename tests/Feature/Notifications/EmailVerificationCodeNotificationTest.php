<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;

test('email verification code notification is sent via mail', function (): void {
    $user = User::factory()->make();
    $notification = new EmailVerificationCodeNotification('123456');

    expect($notification->via($user))->toBe(['mail']);
});

test('email verification code notification mail contains expected content', function (): void {
    $user = User::factory()->make();
    $notification = new EmailVerificationCodeNotification('123456');

    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Verify your email address')
        ->and($mail->greeting)->toBe('Hello!')
        ->and(collect($mail->introLines)->implode(' '))->toContain('123456')
        ->and(collect($mail->introLines)->implode(' '))->toContain('expires in 10 minutes');
});
