<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\HandleGoogleUser;
use App\Actions\Auth\SendEmailVerificationCode;
use App\Actions\Auth\VerifyEmailVerificationCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailCodeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class EmailVerificationCodeController extends Controller
{
    public function store(
        VerifyEmailCodeRequest $request,
        VerifyEmailVerificationCode $verifyEmailVerificationCode,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->ensureGoogleVerificationUser($user);

        if (! $verifyEmailVerificationCode->handle($user, $request->validated('code'))) {
            throw ValidationException::withMessages([
                'code' => [__('The verification code is invalid or has expired.')],
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    public function resend(Request $request, SendEmailVerificationCode $sendEmailVerificationCode): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureGoogleVerificationUser($user);

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $sendEmailVerificationCode->handle($user);

        return back()->with('status', 'verification-code-sent');
    }

    private function ensureGoogleVerificationUser(User $user): void
    {
        if ($user->social_provider !== HandleGoogleUser::PROVIDER) {
            abort(403);
        }
    }
}
