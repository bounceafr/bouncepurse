<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CompletePlayerProfile
{
    public function __construct(private SendGuardianVerification $sendGuardianVerification) {}

    /**
     * @param  array<string, mixed>  $profileData
     * @param  array<string, mixed>|null  $guardianData
     */
    public function handle(User $user, array $profileData, ?array $guardianData = null): void
    {
        DB::transaction(function () use ($user, $profileData, $guardianData): void {
            $user->profile()->updateOrCreate(
                ['player_id' => $user->id],
                $profileData
            );

            if ($guardianData !== null) {
                $guardian = $user->guardian()->updateOrCreate(
                    ['player_id' => $user->id],
                    $guardianData
                );

                $this->sendGuardianVerification->handle($guardian);
            }
        });
    }
}
