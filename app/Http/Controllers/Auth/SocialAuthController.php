<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\HandleGoogleUser;
use App\Actions\Auth\SendEmailVerificationCode;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Throwable;

final class SocialAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(
        Request $request,
        HandleGoogleUser $handleGoogleUser,
        SendEmailVerificationCode $sendEmailVerificationCode,
    ): RedirectResponse {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return to_route('login')
                ->withErrors(['social' => 'Authentication was cancelled or failed. Please try again.']);
        }

        ['user' => $user] = $handleGoogleUser->handle($socialUser);

        if ($user->isDeactivated()) {
            return to_route('login')
                ->withErrors(['social' => 'Your account has been deactivated.']);
        }

        if ($this->shouldSendVerificationCode($user)) {
            $sendEmailVerificationCode->handle($user);
        }

        if ($this->requiresTwoFactorChallenge($user)) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => false,
            ]);

            return to_route('two-factor.login');
        }

        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }

    private function requiresTwoFactorChallenge(User $user): bool
    {
        if (! Features::enabled(Features::twoFactorAuthentication())) {
            return false;
        }

        return $user->two_factor_secret !== null
            && $user->two_factor_confirmed_at !== null;
    }

    private function shouldSendVerificationCode(User $user): bool
    {
        return ! $user->hasVerifiedEmail()
            && $user->social_provider === HandleGoogleUser::PROVIDER;
    }
}
