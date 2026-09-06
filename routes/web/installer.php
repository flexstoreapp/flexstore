<?php

declare(strict_types=1);

use App\Http\Controllers\Installer;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureNotInstalled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleInstallerInertiaRequests;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('install')
    ->name('installer.')
    ->withoutMiddleware([
        EnsureInstalled::class,
        SetLocale::class,
        SetCurrency::class,
        HandleInertiaRequests::class,
    ])
    ->middleware([
        EnsureNotInstalled::class,
        HandleInstallerInertiaRequests::class,
    ])
    ->group(function () {
        Route::get('/', [Installer\RequirementsController::class, 'show'])->name('requirements.show');
        Route::post('/', [Installer\RequirementsController::class, 'store'])->name('requirements.store');

        Route::get('database', [Installer\DatabaseController::class, 'create'])->name('database.create');
        Route::post('database', [Installer\DatabaseController::class, 'store'])->name('database.store');

        Route::get('account', [Installer\AccountController::class, 'create'])->name('account.create');
        Route::post('account', [Installer\AccountController::class, 'store'])->name('account.store');

        Route::get('done', [Installer\CompletionController::class, 'show'])->name('completion.show')->withoutMiddleware(EnsureNotInstalled::class);

        Route::get('finalize', [Installer\FinalizeController::class, 'show'])->name('finalize.show');
        Route::post('finalize', [Installer\FinalizeController::class, 'store'])->name('finalize.store');
    });
