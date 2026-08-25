<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SettingGroup;
use App\Models\Setting;
use App\Utilities\StorefrontHead;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class CheckStorefrontMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::getValue('maintenance_mode', false)) {
            return $next($request);
        }

        $allowedIps = Setting::getValue('maintenance_allowed_ips', []);

        if (is_array($allowedIps) && in_array($request->ip(), $allowedIps, true)) {
            return $next($request);
        }

        $storefrontSettings = Setting::getByGroup(SettingGroup::Storefront);

        StorefrontHead::page(__('Maintenance'));

        return Inertia::render('storefront/maintenance', [
            'theme' => $storefrontSettings->get('storefront_theme_color', 'blue'),
        ])
            ->toResponse($request)
            ->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
