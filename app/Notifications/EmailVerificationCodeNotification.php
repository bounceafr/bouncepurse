<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmailVerificationCodeNotification extends Notification
{
    public function __construct(public string $code) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your email address')
            ->greeting('Hello!')
            ->line('Use the verification code below to confirm your email address.')
            ->line('**'.$this->code.'**')
            ->line('This code expires in 10 minutes.')
            ->line('If you did not create an account, no further action is required.');
    }
}
