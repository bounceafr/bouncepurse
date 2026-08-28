<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CompletePlayerProfile;
use App\Actions\SendGuardianVerification;
use App\Enums\GuardianRelationship;
use App\Http\Requests\CompleteProfileRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OnboardingController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('onboarding/complete-profile', [
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
            'guardianRelationships' => array_map(
                fn (GuardianRelationship $r): array => ['value' => $r->value, 'label' => $r->label()],
                GuardianRelationship::cases()
            ),
            'profile' => $user->profile,
            'guardian' => $user->guardian,
        ]);
    }

    public function store(CompleteProfileRequest $request, CompletePlayerProfile $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, mixed> $profileData */
        $profileData = $request->safe()->except(['profile_image', 'guardian']);

        if ($request->hasFile('profile_image')) {
            $profileData['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
        }

        /** @var array<string, mixed>|null $guardianData */
        $guardianData = $request->isMinorDateOfBirth()
            ? $request->validated('guardian')
            : null;

        $action->handle($user, $profileData, $guardianData);

        if ($request->isMinorDateOfBirth()) {
            return to_route('onboarding.guardian-pending');
        }

        return to_route('dashboard');
    }

    public function guardianPending(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $guardian = $user->guardian;

        if (! $guardian) {
            return to_route('onboarding.complete-profile');
        }

        if ($guardian->isVerified()) {
            return to_route('dashboard');
        }

        return Inertia::render('onboarding/guardian-pending', [
            'guardianEmail' => $this->maskEmail($guardian->email),
            'status' => session('status'),
        ]);
    }

    public function resendGuardianVerification(Request $request, SendGuardianVerification $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $guardian = $user->guardian;

        if ($guardian && ! $guardian->isVerified()) {
            $action->handle($guardian);
        }

        return back()->with('status', 'verification-link-sent');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $maskedLocal = mb_substr($local, 0, 2).str_repeat('*', max(mb_strlen($local) - 2, 0));

        return $maskedLocal.'@'.$domain;
    }
}
