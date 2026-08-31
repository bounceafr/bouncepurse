<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\EmailVerificationCodeController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\GuardianVerificationController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlayerProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', fn (Request $request) => Inertia::render('auth/login', [
    'canResetPassword' => Features::enabled(Features::resetPasswords()),
    'canRegister' => Features::enabled(Features::registration()),
    'status' => $request->session()->get('status'),
]))->name('home');

Route::get('auth/google/redirect', [SocialAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware(['auth'])->group(function (): void {
    Route::post('email/verify-code', [EmailVerificationCodeController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.code.verify');
    Route::post('email/verify-code/resend', [EmailVerificationCodeController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.code.resend');
});

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified', 'player.profile'])->name('dashboard');
Route::get('leaderboard', LeaderboardController::class)->middleware(['auth', 'verified', 'player.profile'])->name('leaderboard');
Route::get('ledger', LedgerController::class)->middleware(['auth', 'verified', 'player.profile'])->name('ledger');
Route::post('games/{game}/dispute', [DisputeController::class, 'store'])->middleware(['auth', 'verified', 'player.profile'])->name('games.dispute.store');

Route::get('players/{user:uuid}', [PlayerProfileController::class, 'show'])->middleware(['auth', 'verified'])->name('players.show');
Route::get('profile', fn (Request $request) => to_route('players.show', $request->user()))->middleware(['auth', 'verified'])->name('profile.show');

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
