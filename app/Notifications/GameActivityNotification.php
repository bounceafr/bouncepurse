<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\GameActivity;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a participant what just happened to their game, by email and in the app. */
final class GameActivityNotification extends Notification
{
    public function __construct(public Game $game, public GameActivity $activity) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello!');

        foreach ($this->lines($notifiable) as $line) {
            $mail->line($line);
        }

        return $mail->action('View Game', $this->url());
    }

    /**
     * @return array{activity: string, game_uuid: string, title: string, message: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'activity' => $this->activity->value,
            'game_uuid' => $this->game->uuid,
            'title' => $this->title(),
            'message' => implode(' ', $this->lines($notifiable)),
            'url' => $this->url(),
        ];
    }

    private function title(): string
    {
        $title = $this->game->title;

        return match ($this->activity) {
            GameActivity::ResultSubmitted => 'Confirm the result of '.$title,
            GameActivity::ConfirmationReminder => 'Reminder: confirm the result of '.$title,
            GameActivity::ResultConfirmed => 'Your result for '.$title.' was confirmed',
            GameActivity::ResultRejected => 'Your result for '.$title.' was rejected',
            GameActivity::ConfirmationExpired => $title.' has gone to a moderator',
            GameActivity::Approved => $title.' was approved',
            GameActivity::Rejected => $title.' was rejected by a moderator',
            GameActivity::Flagged => $title.' was flagged by a moderator',
        };
    }

    /** @return list<string> */
    private function lines(object $notifiable): array
    {
        $game = $this->game;
        $result = $game->gameResult;
        $submittingSide = $game->team->name ?? $game->player->name;
        $opponentSide = $game->opponentTeam->name ?? $game->opponentPlayer->name ?? 'Your opponent';
        $score = $result !== null ? sprintf('%d – %d', $result->your_score, $result->opponent_score) : '';
        $deadline = $result?->confirmationDeadline()->toDayDateTimeString();

        $lines = match ($this->activity) {
            GameActivity::ResultSubmitted => [
                sprintf('%s submitted a final score of %s (theirs – yours) for %s.', $submittingSide, $score, $game->title),
                'The result only counts once you confirm it. If it is wrong, reject it and a moderator will review the game.',
                sprintf('Please answer by %s (UTC).', $deadline),
            ],
            GameActivity::ConfirmationReminder => [
                sprintf('%s is still waiting for you to confirm the score of %s for %s.', $submittingSide, $score, $game->title),
                sprintf('If you have not answered by %s (UTC), a moderator will review the game without your input.', $deadline),
            ],
            GameActivity::ResultConfirmed => [
                sprintf('%s confirmed the score of %s for %s.', $opponentSide, $score, $game->title),
            ],
            GameActivity::ResultRejected => [
                sprintf('%s rejected the score of %s for %s.', $opponentSide, $score, $game->title),
                sprintf('Their reason: "%s"', $result->rejection_reason ?? ''),
                'A moderator will review the game.',
            ],
            GameActivity::ConfirmationExpired => [
                sprintf('%s did not confirm the score of %s for %s in time, so a moderator will review the game.', $opponentSide, $score, $game->title),
            ],
            GameActivity::Approved => [
                sprintf('A moderator approved %s. The result now counts towards rankings.', $game->title),
            ],
            GameActivity::Rejected => [
                sprintf('A moderator rejected %s. It will not count towards rankings.', $game->title),
            ],
            GameActivity::Flagged => [
                sprintf('A moderator flagged %s. If you disagree, you can dispute the decision from the game page.', $game->title),
            ],
        };

        // Only the submitting side can upload the video a moderator needs.
        if ($game->status === GameStatus::AwaitingVideo && $notifiable instanceof User && $game->isOnSubmittingSide($notifiable)) {
            $lines[] = 'Upload a video of the game so a moderator can review it.';
        }

        return $lines;
    }

    private function url(): string
    {
        return route('admin.games.show', $this->game);
    }
}
