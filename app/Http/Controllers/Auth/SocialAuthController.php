<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Team\CreateTeamForUser;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

final class SocialAuthController extends Controller
{
    private const array ALLOWED_PROVIDERS = ['google'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, strict: true), 404);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * @throws Exception
     */
    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, strict: true), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['social' => 'Authentication was cancelled or failed. Please try again.']);
        }

        // Check for an existing user with this social provider
        $user = User::query()
            ->where('social_provider', $provider)
            ->where('social_provider_id', $socialUser->getId())
            ->first();

        if ($user !== null) {
            if ($user->isDeactivated()) {
                return redirect()->route('login')->withErrors(['social' => 'Your account has been deactivated.']);
            }

            Auth::login($user);

            return redirect()->intended(route('dashboard'));
        }

        // Check for an existing user with the same email (e.g. registered via password)
        $user = User::query()->where('email', $socialUser->getEmail())->first();

        if ($user !== null) {
            if ($user->isDeactivated()) {
                return redirect()->route('login')->withErrors(['social' => 'Your account has been deactivated.']);
            }

            $user->update([
                'social_provider' => $provider,
                'social_provider_id' => $socialUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);

            Auth::login($user);

            return redirect()->intended(route('dashboard'));
        }

        // No existing user — create a new account
        $user = User::query()->create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'email_verified_at' => now(),
            'social_provider' => $provider,
            'social_provider_id' => $socialUser->getId(),
        ]);

        $user->assignRole(Role::Player);
        resolve(CreateTeamForUser::class)->handle($user);

        Auth::login($user);

        return redirect()->route('onboarding.complete-profile');
    }
}
