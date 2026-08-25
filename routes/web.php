<?php

declare(strict_types=1);

use App\Http\Controllers\AddressFieldRulesController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TranslationScriptController;
use App\Http\Middleware\ConfigureStorefrontHead;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use Laravel\Head\Inertia\ShareHead;

$translationScriptMiddleware = [
    SetLocale::class,
    SetCurrency::class,
    ConfigureStorefrontHead::class,
    ShareHead::class,
    HandleInertiaRequests::class,
];

Route::get('translations/{locale}/{bundle}/{version}', [TranslationScriptController::class, 'show'])
    ->withoutMiddleware($translationScriptMiddleware)
    ->name('translations.script');

Route::get('address-field-rules/{country}', [AddressFieldRulesController::class, 'show'])->name('address-field-rules.show');

Route::post('webhooks/payment/{driver}', PaymentWebhookController::class)->name('webhooks.payment');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', RobotsController::class)->name('robots');

require __DIR__ . '/installer.php';
require __DIR__ . '/admin.php';
require __DIR__ . '/storefront.php';
