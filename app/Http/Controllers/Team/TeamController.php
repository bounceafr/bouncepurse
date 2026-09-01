<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\UpdateTeamDetails;
use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TeamController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $team = $user->ownedTeam ?? $user->teams()->first();
        $isOwner = $team?->user_id === $user->id;

        $invitations = [];
        $countries = [];

        if ($team !== null && $isOwner) {
            $invitations = $team->invitations()
                ->where('status', InvitationStatus::Pending)
                ->with('invitedBy:id,name')
                ->get();
            $countries = Country::query()->orderBy('name')->get(['id', 'name']);
        }

        return Inertia::render('team/show', [
            'team' => $team?->load('owner'),
            'members' => $team?->members()->get(['users.id', 'users.uuid', 'users.name', 'users.email', 'team_members.joined_at']),
            'invitations' => $invitations,
            'countries' => $countries,
            'isOwner' => $isOwner,
        ]);
    }

    public function update(UpdateTeamRequest $request, UpdateTeamDetails $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $team = $user->ownedTeam;
        abort_unless($team !== null, 403);

        $action->handle($team, $request->validated());

        return back();
    }
}
