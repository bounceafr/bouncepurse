<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

final readonly class VerifyEmailVerificationCode
{
    private const CACHE_PREFIX = 'email-verification-code:';

    public function handle(User $user, string $code): bool
    {
        /** @var ?string $hashedCode */
        $hashedCode = Cache::get(self::CACHE_PREFIX.$user->id);

        if ($hashedCode === null || ! Hash::check($code, $hashedCode)) {
            return false;
        }

        Cache::forget(self::CACHE_PREFIX.$user->id);

        if ($user->hasVerifiedEmail()) {
            return true;
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return true;
    }
}
