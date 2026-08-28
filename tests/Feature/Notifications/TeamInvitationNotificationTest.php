<?php

declare(strict_types=1);

use App\Models\TeamInvitation;
use App\Notifications\TeamInvitationNotification;

test('team invitation notification is sent via mail', function (): void {
    $invitation = TeamInvitation::factory()->create();
    $notification = new TeamInvitationNotification($invitation);

    expect($notification->via($invitation))->toBe(['mail']);
});

test('team invitation notification mail contains expected content', function (): void {
    $invitation = TeamInvitation::factory()->create();
    $notification = new TeamInvitationNotification($invitation);

    $mail = $notification->toMail($invitation);

    expect($mail->subject)->toContain($invitation->team->name)
        ->and($mail->greeting)->toBe('Hello!')
        ->and($mail->actionText)->toBe('Accept Invitation')
        ->and(collect($mail->introLines)->implode(' '))->toContain($invitation->invitedBy->name)
        ->and(collect($mail->introLines)->implode(' '))->toContain($invitation->team->name)
        ->and(collect($mail->outroLines)->implode(' '))->toContain('expires in 7 days');
});
