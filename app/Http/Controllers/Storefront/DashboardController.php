<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Models\User;
use App\Queries\CustomerDashboardQuery;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function index(#[CurrentUser] User $user, CustomerDashboardQuery $query): Response
    {
        StorefrontHead::page(__('Dashboard'));

        return Inertia::render('storefront/account/dashboard', $query->execute($user));
    }
}
