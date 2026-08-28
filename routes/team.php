<?php

declare(strict_types=1);

use App\Http\Controllers\Team\TeamController;
use App\Http\Controllers\Team\TeamInvitationController;
use App\Http\Controllers\Team\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('team', [TeamController::class, 'show'])->name('team.show');
    Route::patch('team', [TeamController::class, 'update'])->name('team.update');

    Route::post('team/invitations', [TeamInvitationController::class, 'store'])->name('team.invitations.store');
    Route::delete('team/invitations/{invitation:uuid}', [TeamInvitationController::class, 'destroy'])->name('team.invitations.destroy');
    Route::delete('team/members/{member:uuid}', [TeamMemberController::class, 'destroy'])->name('team.members.destroy');
});

Route::get('team/invitations/{token}/accept', [TeamInvitationController::class, 'accept'])->name('team.invitations.accept');
Route::get('team/invitations/{token}/decline', [TeamInvitationController::class, 'decline'])->name('team.invitations.decline');
