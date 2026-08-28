<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\VerifyGuardian;
use App\Models\Guardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GuardianVerificationController extends Controller
{
    public function show(string $uuid): Response
    {
        $guardian = Guardian::query()->where('uuid', $uuid)->with('player')->firstOrFail();

        return Inertia::render('onboarding/guardian-verify', [
            'guardian' => [
                'uuid' => $guardian->uuid,
                'full_name' => $guardian->full_name,
                'relationship' => $guardian->relationship->label(),
                'player_name' => $guardian->player->name,
                'is_verified' => $guardian->isVerified(),
            ],
        ]);
    }

    public function verify(Request $request, string $uuid, VerifyGuardian $action): RedirectResponse
    {
        $guardian = Guardian::query()->where('uuid', $uuid)->firstOrFail();

        if (! $guardian->isVerified()) {
            $action->handle($guardian, $request->ip());
        }

        return back()->with('status', 'guardian-verified');
    }
}
