<?php

declare(strict_types=1);

use App\Enums\SettingGroup;
use App\Http\Middleware\ConfigureStorefrontHead;
use App\Http\Middleware\EnsureAccountIsAuthenticated;
use App\Http\Middleware\EnsureGuestCheckoutIsEnabled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsureSchemaIsCurrent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectIfAccountAuthenticated;
use App\Http\Middleware\SetCurrency;
use App\Http\Middleware\SetLocale;
use App\Installer\Contracts\InstallationState;
use App\Models\Setting;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Head\Facades\Head;
use Laravel\Head\Inertia\ShareHead;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(fn (): string => route('admin.dashboard'));
        $middleware->redirectGuestsTo(fn (): string => route('admin.login'));
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'cart_id', 'wishlist_id', 'locale', 'currency']);
        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->web(prepend: [
            EnsureInstalled::class,
            EnsureSchemaIsCurrent::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            SetCurrency::class,
            ConfigureStorefrontHead::class,
            ShareHead::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'account.guest' => RedirectIfAccountAuthenticated::class,
            'account.auth' => EnsureAccountIsAuthenticated::class,
            'checkout.guest' => EnsureGuestCheckoutIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (in_array($response->getStatusCode(), [404, 403, 410]) && resolve(InstallationState::class)->isInstalled()) {
                $storefrontSettings = Setting::getByGroup(SettingGroup::Storefront);
                $theme = $storefrontSettings->get('storefront_theme_color', 'blue');

                // The error page is storefront-themed even on admin URLs.
                Inertia::setRootView('storefront');
                $status = $response->getStatusCode();
                Head::status($status)->title(match ($status) {
                    403 => __('Forbidden'),
                    410 => __('No longer available'),
                    default => __('Page not found'),
                });

                return Inertia::render('storefront/error-page', [
                    'status' => $response->getStatusCode(),
                    'theme' => $theme,
                ])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => 'The page expired, please try again.',
                ]);
            }

            return $response;
        });
    })->create();
