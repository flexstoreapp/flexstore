<?php

declare(strict_types=1);

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Admin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::post('auth/login', [Admin\AdminAccessTokenController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::post('auth/two-factor', Admin\AdminTwoFactorChallengeController::class)
        ->middleware(['auth:sanctum', 'ability:' . TokenAbility::TwoFactorPending->value, 'throttle:6,1'])
        ->name('auth.two-factor');

    Route::middleware(['auth:sanctum', 'ability:' . TokenAbility::Admin->value])->group(function (): void {
        Route::post('auth/logout', [Admin\AdminAccessTokenController::class, 'destroy'])->name('auth.logout');
        Route::get('auth/me', Admin\AdminProfileController::class)->name('auth.me');
    });
});
