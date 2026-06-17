<?php

declare(strict_types=1);

use App\Models\Guardian;
use App\Notifications\GuardianVerificationNotification;

test('guardian verification notification is sent via mail', function (): void {
    $guardian = Guardian::factory()->create();
    $notification = new GuardianVerificationNotification($guardian);

    expect($notification->via($guardian))->toBe(['mail']);
});

test('guardian verification notification mail contains expected content', function (): void {
    $guardian = Guardian::factory()->create();
    $notification = new GuardianVerificationNotification($guardian);

    $mail = $notification->toMail($guardian);

    expect($mail->subject)->toContain($guardian->player->name)
        ->and($mail->greeting)->toContain($guardian->full_name)
        ->and($mail->actionText)->toBe('Verify Guardianship')
        ->and(collect($mail->introLines)->implode(' '))->toContain('under 18')
        ->and(collect($mail->outroLines)->implode(' '))->toContain('expires in 7 days');
});
