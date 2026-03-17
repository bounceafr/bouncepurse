<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Guardian;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class GuardianVerificationNotification extends Notification
{
    public function __construct(public Guardian $guardian) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $guardian = $this->guardian;
        $playerName = $guardian->player->name;

        $url = URL::signedRoute('onboarding.guardian-verify', [
            'uuid' => $guardian->uuid,
        ], now()->addDays(7));

        return (new MailMessage)
            ->subject('Please verify guardianship for '.$playerName)
            ->greeting('Hello '.$guardian->full_name.'!')
            ->line($playerName.' has listed you as their guardian on our platform.')
            ->line('As they are under 18, we need your consent before they can access the platform.')
            ->action('Verify Guardianship', $url)
            ->line('This link expires in 7 days.');
    }
}
