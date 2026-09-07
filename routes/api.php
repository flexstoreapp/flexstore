<?php

declare(strict_types=1);

use App\Http\Middleware\CheckStorefrontMaintenance;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware(CheckStorefrontMaintenance::class)->group(__DIR__ . '/api/v1/storefront.php');
    require __DIR__ . '/api/v1/admin.php';
});
