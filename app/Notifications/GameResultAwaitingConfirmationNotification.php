<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Game;
use App\Models\GameResult;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class GameResultAwaitingConfirmationNotification extends Notification
{
    public function __construct(public Game $game, public GameResult $result) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $submittingSide = $this->game->team->name ?? $this->game->player->name;

        return (new MailMessage)
            ->subject('Confirm the result of '.$this->game->title)
            ->greeting('Hello!')
            ->line(sprintf(
                '%s submitted a final score of %d – %d (theirs – yours) for **%s**.',
                $submittingSide,
                $this->result->your_score,
                $this->result->opponent_score,
                $this->game->title,
            ))
            ->line('The result only counts once you confirm it. If it is wrong, reject it and a moderator will review the game.')
            ->action('Review Result', route('admin.games.show', $this->game));
    }
}
