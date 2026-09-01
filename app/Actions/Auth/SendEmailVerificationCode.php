<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

final readonly class SendEmailVerificationCode
{
    private const string CACHE_PREFIX = 'email-verification-code:';

    private const int TTL_SECONDS = 600;

    public function handle(User $user): void
    {
        $code = mb_str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        Cache::put(
            self::CACHE_PREFIX.$user->id,
            Hash::make($code),
            self::TTL_SECONDS,
        );

        $user->notify(new EmailVerificationCodeNotification($code));
    }
}
