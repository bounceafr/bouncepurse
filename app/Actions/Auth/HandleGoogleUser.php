<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Team\AcceptPendingTeamInvitationForUser;
use App\Actions\Team\CreateTeamForUser;
use App\Enums\Role;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class HandleGoogleUser
{
    public const string PROVIDER = 'google';

    public function __construct(
        private AcceptPendingTeamInvitationForUser $acceptPendingTeamInvitationForUser,
        private CreateTeamForUser $createTeamForUser,
    ) {}

    /**
     * @return array{user: User, isNewUser: bool}
     */
    public function handle(SocialiteUser $socialUser): array
    {
        $user = User::query()
            ->where('social_provider', self::PROVIDER)
            ->where('social_provider_id', $socialUser->getId())
            ->first();

        if ($user !== null) {
            return ['user' => $user, 'isNewUser' => false];
        }

        $user = User::query()->where('email', $socialUser->getEmail())->first();

        if ($user !== null) {
            $user->update([
                'social_provider' => self::PROVIDER,
                'social_provider_id' => $socialUser->getId(),
            ]);

            $user->refresh();

            return ['user' => $user, 'isNewUser' => false];
        }

        $user = User::query()->create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'social_provider' => self::PROVIDER,
            'social_provider_id' => $socialUser->getId(),
        ]);

        $user->assignRole(Role::Player);

        if (! $this->acceptPendingTeamInvitationForUser->handle($user)) {
            $this->createTeamForUser->handle($user);
        }

        return ['user' => $user, 'isNewUser' => true];
    }
}
