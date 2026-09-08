<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Admin;
use Illuminate\Auth\Middleware\Authorize;
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

        Route::get('config', Admin\StoreConfigController::class)->name('config');

        // media
        Route::post('media', [Admin\MediaController::class, 'store'])->middleware(Authorize::using(Permission::MediaUpload))->name('media.store');

        // categories
        Route::get('categories/search', Admin\CategorySearchController::class)->middleware(Authorize::using(Permission::CategoriesReference))->name('categories.search');
        Route::get('categories', [Admin\CategoryController::class, 'index'])->middleware(Authorize::using(Permission::CategoriesView))->name('categories.index');
        Route::post('categories', [Admin\CategoryController::class, 'store'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.store');
        Route::get('categories/{category}', [Admin\CategoryController::class, 'show'])->middleware(Authorize::using(Permission::CategoriesView))->name('categories.show');
        Route::patch('categories/{category}/reorder', [Admin\CategoryReorderController::class, 'update'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.reorder');
        Route::patch('categories/{category}', [Admin\CategoryController::class, 'update'])->middleware(Authorize::using(Permission::CategoriesManage))->name('categories.update');
        Route::delete('categories/{category}', [Admin\CategoryController::class, 'destroy'])->middleware(Authorize::using(Permission::CategoriesDelete))->name('categories.destroy');
    });
});
