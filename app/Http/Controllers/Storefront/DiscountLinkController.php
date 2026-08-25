<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class DiscountLinkController
{
    public function __invoke(#[RouteParameter('code')] string $code, Request $request): RedirectResponse
    {
        $request->session()->put('pending_coupon', mb_trim($code));

        return to_route('home');
    }
}
