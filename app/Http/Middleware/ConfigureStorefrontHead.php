<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SettingGroup;
use App\Models\Setting;
use App\Utilities\AdminPath;
use Closure;
use Illuminate\Http\Request;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;
use Symfony\Component\HttpFoundation\Response;

final readonly class ConfigureStorefrontHead
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (AdminPath::matches($request) || $request->routeIs('installer.*') || $request->is('install', 'install/*')) {
            Head::hiddenFromRobots();

            return $next($request);
        }

        $storeName = (string) Setting::getValue('store_name', config('app.name', 'FlexStore'));

        Head::title($storeName, suffix: ' - ' . $storeName, exact: true)
            ->og(type: OgType::Website, siteName: $storeName, locale: app()->getLocale())
            ->twitter(card: TwitterCard::SummaryWithLargeImage)
            ->themeColor('#ffffff');

        if ((bool) Setting::getValue('seo_robots_indexing', true)) {
            Head::searchableByRobots();
        } else {
            Head::hiddenFromRobots();
        }

        $this->applyCanonical($request);

        return $next($request);
    }

    private function applyCanonical(Request $request): void
    {
        $locales = $request->attributes->get('available_locales', []);

        if (! is_array($locales) || count($locales) < 2) {
            Head::canonical();

            return;
        }

        $baseUrl = url()->current();
        $queryParams = collect($request->query())->except('lang')->all();
        $buildUrl = function (array $extra = []) use ($baseUrl, $queryParams): string {
            $query = http_build_query(array_merge($queryParams, $extra));

            return $baseUrl . ($query !== '' ? '?' . $query : '');
        };

        $defaultLocale = (string) Setting::getByGroup(SettingGroup::Locale)
            ->get('default_locale', config('app.locale', 'en'));
        $alternates = [];

        foreach ($locales as $locale) {
            if (is_string($locale)) {
                $alternates[$locale] = $buildUrl(['lang' => $locale]);
            }
        }

        $alternates['x-default'] = $buildUrl();

        Head::alternates($alternates)
            ->canonical($buildUrl(app()->getLocale() === $defaultLocale ? [] : ['lang' => app()->getLocale()]));
    }
}
