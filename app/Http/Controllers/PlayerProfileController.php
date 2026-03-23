<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Ranking\GetPlayerRankingsAction;
use App\Enums\GameStatus;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

final class PlayerProfileController extends Controller
{
    public function show(User $user, GetPlayerRankingsAction $rankingsAction): Response
    {
        $user->load('profile.country');

        $totalGames = $user->games()->count();
        $approvedGames = $user->games()->where('status', GameStatus::Approved)->count();

        return Inertia::render('players/show', [
            'player' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'profile_image' => $user->profile?->profile_image,
                'country' => $user->profile?->country?->name,
                'city' => $user->profile?->city,
                'position' => $user->profile?->position,
                'bio' => $user->profile?->bio,
                'is_pathway_candidate' => $user->profile?->is_pathway_candidate ?? false,
                'member_since' => $user->created_at?->toISOString(),
            ],
            'rankings' => $rankingsAction->handle($user->id),
            'game_stats' => [
                'total' => $totalGames,
                'approved' => $approvedGames,
            ],
        ]);
    }
}
