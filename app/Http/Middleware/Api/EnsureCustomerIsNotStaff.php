<?php

declare(strict_types=1);

namespace App\Http\Middleware\Api;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The customer endpoints bind any user row, so a staff account must not be
 * reachable, read or written through them.
 */
final readonly class EnsureCustomerIsNotStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->route('customer');

        abort_if($customer instanceof User && $customer->hasAdminAccess(), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
