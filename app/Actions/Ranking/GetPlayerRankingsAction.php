<?php

declare(strict_types=1);

namespace App\Actions\Ranking;

use App\Models\PlayerRanking;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GetPlayerRankingsAction
{
    /**
     * @return array<string, array{format: string, rank: int, score: float, wins: int, losses: int}>
     */
    public function handle(int $playerId): array
    {
        $latestSubquery = PlayerRanking::query()
            ->where('player_id', $playerId)
            ->select('format', DB::raw('MAX(calculated_at) as max_calculated_at'))
            ->groupBy('format');

        /** @var Collection<int, PlayerRanking> $rankings */
        $rankings = PlayerRanking::query()
            ->where('player_rankings.player_id', $playerId)
            ->joinSub($latestSubquery, 'latest', function ($join): void {
                $join->on('player_rankings.format', '=', 'latest.format')
                    ->on('player_rankings.calculated_at', '=', 'latest.max_calculated_at');
            })
            ->get();

        $result = [];

        foreach ($rankings as $ranking) {
            $result[$ranking->format] = [
                'format' => $ranking->format,
                'rank' => $ranking->rank,
                'score' => $ranking->score,
                'wins' => $ranking->wins,
                'losses' => $ranking->losses,
            ];
        }

        return $result;
    }
}
