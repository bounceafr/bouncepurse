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

        $action->handle($user->ownedTeam, $member);

        return back();
    }
}
