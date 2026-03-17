<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardianVerificationController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn () => Inertia::render('auth/login', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified', 'player.profile'])->name('dashboard');
Route::get('leaderboard', LeaderboardController::class)->middleware(['auth', 'verified', 'player.profile'])->name('leaderboard');
Route::get('ledger', LedgerController::class)->middleware(['auth', 'verified', 'player.profile'])->name('ledger');

// Onboarding (auth + verified, but NOT player.profile)
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('onboarding/complete-profile', [OnboardingController::class, 'show'])->name('onboarding.complete-profile');
    Route::post('onboarding/complete-profile', [OnboardingController::class, 'store'])->name('onboarding.store-profile');
    Route::get('onboarding/guardian-pending', [OnboardingController::class, 'guardianPending'])->name('onboarding.guardian-pending');
    Route::post('onboarding/resend-guardian-verification', [OnboardingController::class, 'resendGuardianVerification'])
        ->name('onboarding.resend-guardian-verification')
        ->middleware('throttle:3,1');
});

// Public guardian verification (signed URL, no auth)
Route::get('guardian/verify/{uuid}', [GuardianVerificationController::class, 'show'])->name('onboarding.guardian-verify')->middleware('signed');
Route::post('guardian/verify/{uuid}', [GuardianVerificationController::class, 'verify'])->name('onboarding.guardian-confirm')->middleware('signed');

require __DIR__.'/settings.php';
require __DIR__.'/team.php';
require __DIR__.'/admin.php';
