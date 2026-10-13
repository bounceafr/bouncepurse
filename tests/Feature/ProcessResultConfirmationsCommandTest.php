<?php

declare(strict_types=1);

use App\Enums\GameActivity;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;
use App\Notifications\GameActivityNotification;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

beforeEach(function (): void {
    Notification::fake();
    $this->travelTo('2026-10-13 10:00:00');
});

/** A game whose result was submitted the given number of hours ago and is still unanswered. */
function unansweredFor(int $hours, bool $videoReady = true, ?User $opponent = null): Game
{
    $factory = Game::factory()->againstPlayer($opponent)->awaitingConfirmation();
    $game = ($videoReady ? $factory->withReadyVideo() : $factory)->create();
    GameResult::factory()->create(['game_id' => $game->id, 'submitter_id' => $game->player_id, 'created_at' => now()->subHours($hours)]);

    return $game;
}

function processResultConfirmations(TestCase $test): PendingCommand
{
    $command = $test->artisan('app:process-result-confirmations');
    assert($command instanceof PendingCommand);

    return $command;
}

function notifiedOf(User $user, GameActivity $activity): bool
{
    return Notification::sent($user, GameActivityNotification::class)
        ->contains(fn (GameActivityNotification $notification): bool => $notification->activity === $activity);
}

it('reminds the opponent once after two days', function (): void {
    $opponent = User::factory()->create();
    $game = unansweredFor(49, opponent: $opponent);

    processResultConfirmations($this)
        ->expectsOutput(sprintf('Reminded the opponent in game %d.', $game->id))
        ->assertSuccessful();
    processResultConfirmations($this)->assertSuccessful();

    Notification::assertSentToTimes($opponent, GameActivityNotification::class, 1);
    expect(notifiedOf($opponent, GameActivity::ConfirmationReminder))->toBeTrue()
        ->and($game->gameResult()->sole()->confirmation_reminded_at)->not->toBeNull()
        ->and($game->refresh()->status)->toBe(GameStatus::AwaitingConfirmation);
});

it('leaves recent results alone', function (): void {
    $game = unansweredFor(10);

    processResultConfirmations($this)->assertSuccessful();

    Notification::assertNothingSent();
    expect($game->refresh()->status)->toBe(GameStatus::AwaitingConfirmation);
});

it('sends an unanswered result to moderation as contested after five days', function (): void {
    $opponent = User::factory()->create();
    $game = unansweredFor(121, opponent: $opponent);
    $submitter = User::query()->findOrFail($game->player_id);

    processResultConfirmations($this)
        ->expectsOutput(sprintf('Game %d was not confirmed in time and went to moderation.', $game->id))
        ->assertSuccessful();

    expect($game->refresh()->status)->toBe(GameStatus::Contested)
        ->and($game->gameResult()->sole()->confirmation_expired_at)->not->toBeNull()
        ->and(notifiedOf($submitter, GameActivity::ConfirmationExpired))->toBeTrue()
        ->and(notifiedOf($opponent, GameActivity::ConfirmationExpired))->toBeTrue()
        ->and(notifiedOf($opponent, GameActivity::ConfirmationReminder))->toBeFalse();
});

it('holds an expired result without a video until the video is ready', function (): void {
    $game = unansweredFor(121, videoReady: false);

    processResultConfirmations($this)->assertSuccessful();

    expect($game->refresh()->status)->toBe(GameStatus::AwaitingVideo)
        ->and($game->moderationEntryStatus())->toBe(GameStatus::AwaitingVideo);
});

it('does not expire a result the opponent answered in the meantime', function (): void {
    $game = unansweredFor(121);

    // Simulate the opponent confirming after the command selected the game.
    Game::retrieved(function (Game $retrieved) use ($game): void {
        if ($retrieved->id === $game->id) {
            DB::table('games')->where('id', $game->id)->update(['status' => GameStatus::Pending->value]);
        }
    });

    processResultConfirmations($this)->assertSuccessful();

    Notification::assertNothingSent();
    expect($game->gameResult()->sole()->confirmation_expired_at)->toBeNull();
});

it('does not remind twice when another run reminded first', function (): void {
    $game = unansweredFor(49);

    Game::retrieved(function (Game $retrieved) use ($game): void {
        if ($retrieved->id === $game->id) {
            DB::table('game_results')->where('game_id', $game->id)->update(['confirmation_reminded_at' => now()]);
        }
    });

    processResultConfirmations($this)->assertSuccessful();

    Notification::assertNothingSent();
});

it('is scheduled hourly', function (): void {
    $event = collect(resolve(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'app:process-result-confirmations'));

    expect($event)->not->toBeNull()
        ->and($event?->expression)->toBe('0 * * * *');
});
