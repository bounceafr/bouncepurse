<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Admin\Allocation\CreateAllocation;
use App\Actions\Pathway\RecalculateAllPathwayEligibilityAction;
use App\Actions\Ranking\CalculateRankingsAction;
use App\Enums\GameStatus;
use App\Enums\ResultStatus;
use App\Enums\Role;
use App\Models\Court;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\PathwayConfiguration;
use App\Models\RankingConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Random\RandomException;
use Throwable;

final class GameSeeder extends Seeder
{
    /**
     * @throws Throwable
     * @throws RandomException
     */
    public function run(): void
    {
        $players = User::query()->role(Role::Player->value)->get();
        $moderators = User::query()->role(Role::Moderator->value)->get();
        $courts = Court::query()->get();
        $config = RankingConfiguration::query()->latest('id')->firstOrFail();
        $createAllocation = resolve(CreateAllocation::class);
        $formats = ['1v1', '2v2', '3v3', '4v4', '5v5'];

        // Pick 5 players to be pathway-eligible (they'll get concentrated approved games)
        $pathwayPlayers = $players->random(min(5, $players->count()));
        $players->diff($pathwayPlayers);

        // Step A — Approved games (60 general + 50 pathway-targeted)
        /** @var Collection<int, Game> $rejectedGames */
        $rejectedGames = collect();

        // A1 — Give each pathway player 10 approved games across formats
        foreach ($pathwayPlayers as $pathwayPlayer) {
            foreach ($formats as $format) {
                for ($i = 0; $i < 2; $i++) {
                    $playedAt = $i === 0
                        ? fake()->dateTimeBetween('-30 days', 'now')
                        : fake()->dateTimeBetween('-1 year', '-31 days');

                    $game = Game::factory()->create([
                        'player_id' => $pathwayPlayer->id,
                        'format' => $format,
                        'status' => GameStatus::Approved->value,
                        'result' => fake()->randomElement([ResultStatus::WIN->value, ResultStatus::LOST->value]),
                        'court_id' => random_int(1, 10) > 3 ? $courts->random()->id : null,
                        'played_at' => $playedAt,
                    ]);

                    GameModeration::query()->create([
                        'game_id' => $game->id,
                        'moderator_id' => $moderators->random()->id,
                        'status' => GameStatus::Approved->value,
                        'reason' => fake()->sentence(),
                        'is_override' => false,
                    ]);

                    $createAllocation->handle($game);
                }
            }
        }

        // A2 — Spread remaining approved games across all players
        foreach ($formats as $format) {
            for ($i = 0; $i < 12; $i++) {
                $playedAt = $i < 6
                    ? fake()->dateTimeBetween('-30 days', 'now')
                    : fake()->dateTimeBetween('-1 year', '-31 days');

                $game = Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Approved->value,
                    'result' => fake()->randomElement([ResultStatus::WIN->value, ResultStatus::LOST->value]),
                    'court_id' => random_int(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => $playedAt,
                ]);

                GameModeration::query()->create([
                    'game_id' => $game->id,
                    'moderator_id' => $moderators->random()->id,
                    'status' => GameStatus::Approved->value,
                    'reason' => fake()->sentence(),
                    'is_override' => false,
                ]);

                $createAllocation->handle($game);
            }
        }

        // Step B — Rejected games (15 total, 3 per format)
        foreach ($formats as $format) {
            for ($i = 0; $i < 3; $i++) {
                $game = Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Rejected->value,
                    'result' => null,
                    'court_id' => random_int(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
                ]);

                GameModeration::query()->create([
                    'game_id' => $game->id,
                    'moderator_id' => $moderators->random()->id,
                    'status' => GameStatus::Rejected->value,
                    'reason' => fake()->sentence(),
                    'is_override' => false,
                ]);

                $rejectedGames->push($game);
            }
        }

        // Step C — Flagged games (10 total, 2 per format)
        foreach ($formats as $format) {
            for ($i = 0; $i < 2; $i++) {
                $game = Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Flagged->value,
                    'result' => null,
                    'court_id' => random_int(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
                ]);

                GameModeration::query()->create([
                    'game_id' => $game->id,
                    'moderator_id' => $moderators->random()->id,
                    'status' => GameStatus::Flagged->value,
                    'reason' => fake()->sentence(),
                    'is_override' => false,
                ]);
            }
        }

        // Step D — Pending games (15 total, 3 per format)
        foreach ($formats as $format) {
            for ($i = 0; $i < 3; $i++) {
                Game::factory()->create([
                    'player_id' => $players->random()->id,
                    'format' => $format,
                    'status' => GameStatus::Pending->value,
                    'result' => null,
                    'court_id' => random_int(1, 10) > 3 ? $courts->random()->id : null,
                    'played_at' => fake()->dateTimeBetween('-1 year', 'now'),
                ]);
            }
        }

        // Step E — Override scenario (3 rejected games get approved via override)
        $overrideGames = $rejectedGames->take(3);

        foreach ($overrideGames as $game) {
            GameModeration::query()->create([
                'game_id' => $game->id,
                'moderator_id' => $moderators->random()->id,
                'status' => GameStatus::Approved->value,
                'reason' => fake()->sentence(),
                'is_override' => true,
            ]);

            $game->update([
                'status' => GameStatus::Approved->value,
                'result' => fake()->randomElement([ResultStatus::WIN->value, ResultStatus::LOST->value]),
            ]);

            $createAllocation->handle($game);
        }

        // Step F — Calculate Rankings
        resolve(CalculateRankingsAction::class)->handle($config);

        // Step G — Recalculate Pathway Eligibility
        $pathwayConfig = PathwayConfiguration::query()->latest()->first();

        if ($pathwayConfig !== null) {
            resolve(RecalculateAllPathwayEligibilityAction::class)->handle($pathwayConfig);
        }
    }
}
