<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Models\Setting;
use App\Utilities\AdminPath;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AdminPath::matches($request)) {
            return $next($request);
        }

        $baseCurrency = (string) Setting::getValue('base_currency', 'USD');

        $availableCurrencies = Currency::query()
            ->where('is_active', true)
            ->pluck('code')
            ->all();

        if (empty($availableCurrencies)) {
            $availableCurrencies = [$baseCurrency];
        }

        $currencyParam = is_string($request->query('currency')) ? mb_strtoupper($request->query('currency')) : null;
        $cookieValue = $request->cookie('currency');
        $cookieCurrency = is_string($cookieValue) ? mb_strtoupper($cookieValue) : null;
        $sessionCurrency = session('currency') ? mb_strtoupper((string) session('currency')) : null;

        $currency = $currencyParam ?? $cookieCurrency ?? $sessionCurrency ?? $baseCurrency;

        if (! in_array($currency, $availableCurrencies, true)) {
            $currency = $baseCurrency;
        }

        if ($currencyParam !== null && in_array($currencyParam, $availableCurrencies, true)) {
            session(['currency' => $currencyParam]);
            Cookie::queue(Cookie::forever('currency', $currencyParam));
        }

        $request->attributes->set('active_currency', $currency);

        return $next($request);
    }
}
