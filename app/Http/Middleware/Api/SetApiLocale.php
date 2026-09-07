<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use App\Enums\SettingGroup;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetApiLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = Setting::getByGroup(SettingGroup::Locale);
        /** @var list<string> $available */
        $available = (array) $settings->get('available_locales', [config('app.locale', 'en')]);
        $default = (string) $settings->get('default_locale', config('app.locale', 'en'));

        $requested = $request->header('Accept-Language') !== null
            ? $request->getPreferredLanguage($available)
            : null;

        $locale = in_array($requested, $available, true) ? $requested : $default;

        app()->setLocale($locale);
        app()->setFallbackLocale($default);

        $request->attributes->set('available_locales', $available);

        return $next($request);
    }
}
