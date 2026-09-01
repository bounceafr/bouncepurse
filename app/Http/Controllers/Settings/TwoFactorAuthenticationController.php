<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

final class TwoFactorAuthenticationController extends Controller
{
    /**
     * Show the user's two-factor authentication settings page.
     */
    public function show(TwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();

        /** @var User $user */
        $user = $request->user();

        $confirmPassword = Features::optionEnabled(
            Features::twoFactorAuthentication(),
            'confirmPassword',
        );

        return Inertia::render('settings/two-factor', [
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            'confirmPassword' => $confirmPassword,
            'passwordConfirmed' => ! $confirmPassword || $this->passwordIsConfirmed($request),
        ]);
    }

    /**
     * Determine whether the user's password has been recently confirmed.
     */
    private function passwordIsConfirmed(Request $request): bool
    {
        $confirmedAt = Date::now()->unix() - $request->session()->get('auth.password_confirmed_at', 0);

        return $confirmedAt < (int) config('auth.password_timeout', 10800);
    }
}
