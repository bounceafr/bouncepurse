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

        return Inertia::render('team/show', [
            'team' => $team?->load('owner'),
            'members' => $team?->members()->get(['users.id', 'users.uuid', 'users.name', 'users.email', 'team_members.joined_at']),
            'invitations' => $isOwner
                ? $team?->invitations()
                    ->where('status', InvitationStatus::Pending)
                    ->with('invitedBy:id,name')
                    ->get()
                : [],
            'countries' => $isOwner
                ? Country::query()->orderBy('name')->get(['id', 'name'])
                : [],
            'isOwner' => $isOwner,
        ]);
    }

    public function update(UpdateTeamRequest $request, UpdateTeamDetails $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user->ownedTeam, $request->validated());

        return back();
    }
}
