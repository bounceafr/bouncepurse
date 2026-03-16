<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Actions\Team\AcceptTeamInvitation;
use App\Actions\Team\CreateTeamForUser;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::query()->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $user->assignRole(Role::Player);

        if (! $this->acceptPendingInvitation($user)) {
            app(CreateTeamForUser::class)->handle($user);
        }

        return $user;
    }

    private function acceptPendingInvitation(User $user): bool
    {
        $token = session()->pull('team_invitation_token');

        if (! $token) {
            return false;
        }

        $invitation = TeamInvitation::query()
            ->where('token', $token)
            ->where('email', $user->email)
            ->where('status', InvitationStatus::Pending)
            ->first();

        if (! $invitation) {
            return false;
        }

        app(AcceptTeamInvitation::class)->handle($invitation, $user);

        return true;
    }
}
