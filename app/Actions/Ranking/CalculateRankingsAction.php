<?php

declare(strict_types=1);

namespace App\Actions\Ranking;

use App\Enums\GameStatus;
use App\Enums\ResultStatus;
use App\Models\Game;
use App\Models\PlayerRanking;
use App\Models\RankingConfiguration;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

final class CalculateRankingsAction
{
    public function handle(RankingConfiguration $config): void
    {
        $calculatedAt = now();
        $thirtyDaysAgo = now()->subDays(30);

        /** @var Collection<int, Game> $games */
        $games = Game::query()
            ->withoutGlobalScopes()
            ->with([
                'gameResult:id,game_id,your_score,opponent_score',
                'team.members:id',
                'opponentTeam.members:id',
            ])
            ->where('status', GameStatus::Approved)
            ->select(['id', 'player_id', 'team_id', 'opponent_player_id', 'opponent_team_id', 'format', 'result', 'played_at'])
            ->get();

        /** @var array<string, array<int, array{wins: int, losses: int, total: int, recent: int}>> $grouped */
        $grouped = [];

        foreach ($games as $game) {
            $format = $game->format->value;

            // Submitted scores are authoritative; the stored result only covers games recorded without them.
            $outcome = $game->gameResult?->outcome() ?? $game->result;
            $isRecent = $game->played_at >= $thirtyDaysAgo;

            [$creatorSide, $opponentSide] = $this->sides($game);

            foreach ([[$creatorSide, $outcome], [$opponentSide, $outcome?->opposite()]] as [$playerIds, $sideOutcome]) {
                foreach ($playerIds as $playerId) {
                    $grouped[$format][$playerId] ??= [
                        'wins' => 0,
                        'losses' => 0,
                        'total' => 0,
                        'recent' => 0,
                    ];

                    $grouped[$format][$playerId]['total']++;

                    if ($sideOutcome === ResultStatus::WIN) {
                        $grouped[$format][$playerId]['wins']++;
                    } elseif ($sideOutcome === ResultStatus::LOST) {
                        $grouped[$format][$playerId]['losses']++;
                    }

                    if ($isRecent) {
                        $grouped[$format][$playerId]['recent']++;
                    }
                }
            }
        }

        $rows = [];

        foreach ($grouped as $format => $players) {
            $scored = [];

            foreach ($players as $playerId => $stats) {
                $score = ($stats['wins'] * $config->win_weight)
                    + ($stats['losses'] * $config->loss_weight)
                    + ($stats['total'] * $config->game_count_weight)
                    + ($stats['recent'] * $config->frequency_weight);

                $scored[] = [
                    'player_id' => $playerId,
                    'wins' => $stats['wins'],
                    'losses' => $stats['losses'],
                    'total_games' => $stats['total'],
                    'recent_games' => $stats['recent'],
                    'score' => $score,
                ];
            }

            usort($scored, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

            foreach ($scored as $rank => $entry) {
                $rows[] = [
                    'player_id' => $entry['player_id'],
                    'format' => $format,
                    'wins' => $entry['wins'],
                    'losses' => $entry['losses'],
                    'total_games' => $entry['total_games'],
                    'recent_games' => $entry['recent_games'],
                    'score' => round($entry['score'], 4),
                    'rank' => $rank + 1,
                    'ranking_configuration_id' => $config->id,
                    'calculated_at' => $calculatedAt,
                    'created_at' => $calculatedAt,
                    'updated_at' => $calculatedAt,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            PlayerRanking::query()->insert($chunk);
        }
    }

    /**
     * Everyone credited on each side: the players themselves, or every current member of each team.
     * A player found on both sides is only credited with the creator's side.
     *
     * @return array{list<int>, list<int>}
     */
    private function sides(Game $game): array
    {
        $creatorSide = $game->team !== null
            ? array_values(array_unique([$game->player_id, ...$this->memberIds($game->team)]))
            : [$game->player_id];

        $opponentSide = match (true) {
            $game->opponentTeam !== null => $this->memberIds($game->opponentTeam),
            $game->opponent_player_id !== null => [$game->opponent_player_id],
            default => [],
        };

        return [$creatorSide, array_values(array_diff($opponentSide, $creatorSide))];
    }

    /** @return list<int> */
    private function memberIds(Team $team): array
    {
        return array_values($team->members->map(fn (User $member): int => $member->id)->all());
    }
}
