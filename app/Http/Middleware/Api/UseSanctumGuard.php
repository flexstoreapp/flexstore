<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The default guard is the web session, which an API request never has. Without
 * this, a bearer token is ignored on every route that is not behind
 * `auth:sanctum`, so a signed-in customer would shop and check out as a guest.
 */
final readonly class UseSanctumGuard
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
