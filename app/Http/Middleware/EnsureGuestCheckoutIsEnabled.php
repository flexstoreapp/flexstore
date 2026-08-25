<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureGuestCheckoutIsEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() || (bool) Setting::getValue('guest_checkout_enabled', true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(
                ['message' => __('Please sign in to complete your order.')],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return redirect()->guest(route('account.login'));
    }
}
