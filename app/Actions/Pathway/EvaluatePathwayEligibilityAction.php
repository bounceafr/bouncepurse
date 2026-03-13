<?php

declare(strict_types=1);

namespace App\Actions\Pathway;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameModeration;
use App\Models\PathwayConfiguration;
use App\Models\PlayerRanking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class EvaluatePathwayEligibilityAction
{
    /**
     * @return array{is_eligible: bool, criteria: array{approved_games: array{required: int, current: int, met: bool}, rank: array{required: int, current: ?int, met: bool}, conduct_flags: array{limit: int, current: int, met: bool}}}
     */
    public function handle(int $playerId, PathwayConfiguration $config): array
    {
        $approvedGames = Game::query()->withoutGlobalScopes()
            ->where('player_id', $playerId)
            ->where('status', GameStatus::Approved)
            ->count();

        $bestRank = $this->getBestRank($playerId);

        $conductFlags = GameModeration::query()
            ->whereHas('game', fn (Builder $q) => $q->withoutGlobalScopes()->where('player_id', $playerId))
            ->where('status', GameStatus::Flagged)
            ->count();

        $gamesMet = $approvedGames >= $config->min_approved_games;
        $rankMet = $bestRank !== null && $bestRank <= $config->max_rank;
        $conductMet = $conductFlags <= $config->max_conduct_flags;

        return [
            'is_eligible' => $gamesMet && $rankMet && $conductMet,
            'criteria' => [
                'approved_games' => [
                    'required' => $config->min_approved_games,
                    'current' => $approvedGames,
                    'met' => $gamesMet,
                ],
                'rank' => [
                    'required' => $config->max_rank,
                    'current' => $bestRank,
                    'met' => $rankMet,
                ],
                'conduct_flags' => [
                    'limit' => $config->max_conduct_flags,
                    'current' => $conductFlags,
                    'met' => $conductMet,
                ],
            ],
        ];
    }

    private function getBestRank(int $playerId): ?int
    {
        $latestPerFormat = PlayerRanking::query()
            ->where('player_id', $playerId)
            ->select('format', DB::raw('MAX(calculated_at) as max_calculated_at'))
            ->groupBy('format')
            ->get();

        if ($latestPerFormat->isEmpty()) {
            return null;
        }

        $bestRank = null;

        foreach ($latestPerFormat as $row) {
            $ranking = PlayerRanking::query()
                ->where('player_id', $playerId)
                ->where('format', $row->format)
                ->where('calculated_at', $row->max_calculated_at)
                ->first();

            if ($ranking !== null && ($bestRank === null || $ranking->rank < $bestRank)) {
                $bestRank = $ranking->rank;
            }
        }

        return $bestRank;
    }
}
