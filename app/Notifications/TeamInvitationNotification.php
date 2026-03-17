<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TeamInvitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TeamInvitationNotification extends Notification
{
    public function __construct(public TeamInvitation $invitation) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $invitation = $this->invitation;
        $team = $invitation->team;
        $inviter = $invitation->invitedBy;

        return (new MailMessage)
            ->subject("You've been invited to join ".$team->name)
            ->greeting('Hello!')
            ->line(sprintf('%s has invited you to join their team **%s**.', $inviter->name, $team->name))
            ->action('Accept Invitation', route('team.invitations.accept', $invitation->token))
            ->line('Or [decline this invitation]('.route('team.invitations.decline', $invitation->token).').')
            ->line('This invitation expires in 7 days.');
    }
}
