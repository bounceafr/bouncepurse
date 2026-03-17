<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\RemoveTeamMember;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TeamMemberController extends Controller
{
    public function destroy(Request $request, User $member, RemoveTeamMember $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $team = $user->ownedTeam;

        abort_unless($team !== null, 403);

        $action->handle($team, $member);

        return back();
    }
}
