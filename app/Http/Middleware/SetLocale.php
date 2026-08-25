<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SettingGroup;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $localeSettings = Setting::getByGroup(SettingGroup::Locale);
        $availableLocales = (array) $localeSettings->get('available_locales', [config('app.locale', 'en')]);
        $defaultLocale = (string) $localeSettings->get('default_locale', config('app.locale', 'en'));

        $langParam = is_string($request->query('lang')) ? $request->query('lang') : null;
        $browserLocale = $this->detectBrowserLocale($request, $availableLocales);

        $locale = $langParam
            ?? $request->cookie('locale')
            ?? session('locale')
            ?? $browserLocale
            ?? $defaultLocale;

        if (! in_array($locale, $availableLocales, true)) {
            $locale = $defaultLocale;
        }

        if ($langParam !== null && in_array($langParam, $availableLocales, true)) {
            session(['locale' => $langParam]);
            Cookie::queue(Cookie::forever('locale', $langParam));
        } elseif ($browserLocale !== null && $locale === $browserLocale && $request->isMethod('GET') && ! $request->cookie('locale') && ! session('locale')) {
            session(['locale' => $browserLocale]);
            Cookie::queue(Cookie::forever('locale', $browserLocale));
        }

        app()->setLocale($locale);
        app()->setFallbackLocale($defaultLocale);

        $request->attributes->set('available_locales', $availableLocales);

        return $next($request);
    }

    /**
     * @param  array<int, string>  $availableLocales
     */
    private function detectBrowserLocale(Request $request, array $availableLocales): ?string
    {
        $preferred = $request->getPreferredLanguage($availableLocales);

        if ($preferred !== null && in_array($preferred, $availableLocales, true)) {
            return $preferred;
        }

        return null;
    }
}
