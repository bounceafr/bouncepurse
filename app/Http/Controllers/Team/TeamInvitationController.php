<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\AcceptTeamInvitation;
use App\Actions\Team\CancelTeamInvitation;
use App\Actions\Team\DeclineTeamInvitation;
use App\Actions\Team\SendTeamInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\SendInvitationRequest;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TeamInvitationController extends Controller
{
    public function store(SendInvitationRequest $request, SendTeamInvitation $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle($user->ownedTeam, $request->validated()['email'], $user);

        return back();
    }

    public function accept(Request $request, string $token, AcceptTeamInvitation $action): RedirectResponse
    {
        if (! $request->user()) {
            $invitation = TeamInvitation::query()->where('token', $token)->firstOrFail();

            $request->session()->put('team_invitation_token', $token);

            return redirect()->route('register', ['email' => $invitation->email]);
        }

        $invitation = TeamInvitation::query()->where('token', $token)->firstOrFail();

        /** @var User $user */
        $user = $request->user();

        $action->handle($invitation, $user);

        return to_route('team.show');
    }

    public function decline(string $token, DeclineTeamInvitation $action): RedirectResponse
    {
        $invitation = TeamInvitation::query()->where('token', $token)->firstOrFail();

        $action->handle($invitation);

        return to_route('home');
    }

    public function destroy(TeamInvitation $invitation, CancelTeamInvitation $action): RedirectResponse
    {
        $action->handle($invitation);

        return back();
    }
}
