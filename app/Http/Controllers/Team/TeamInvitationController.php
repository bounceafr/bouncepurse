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

        $team = $user->ownedTeam;
        abort_unless($team !== null, 403);

        $validated = $request->validated();
        /** @var array{email: string} $validated */
        $action->handle($team, $validated['email'], $user);

        return back();
    }

    public function accept(Request $request, string $token, AcceptTeamInvitation $action): RedirectResponse
    {
        $invitation = TeamInvitation::query()->with('team')->where('token', $token)->firstOrFail();

        if (! $request->user()) {
            $request->session()->put('team_invitation_token', $token);

            return to_route('register', ['email' => $invitation->email]);
        }

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

    public function destroy(Request $request, TeamInvitation $invitation, CancelTeamInvitation $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_if($invitation->team->user_id !== $user->id, 403);

        $action->handle($invitation);

        return back();
    }
}
