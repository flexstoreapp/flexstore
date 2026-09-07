<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use App\Models\Currency;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetApiCurrency
{
    public const string HEADER = 'X-Currency';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $base = (string) Setting::getValue('base_currency', 'USD');

        $available = Currency::query()
            ->where('is_active', true)
            ->pluck('code')
            ->all();

        if ($available === []) {
            $available = [$base];
        }

        $requested = $request->header(self::HEADER);
        $requested = is_string($requested) ? mb_strtoupper($requested) : null;

        $request->attributes->set(
            'active_currency',
            in_array($requested, $available, true) ? $requested : $base,
        );

        return $next($request);
    }
}
