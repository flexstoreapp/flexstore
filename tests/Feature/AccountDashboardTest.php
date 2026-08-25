<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\DashboardController;
use App\Models\Order;
use App\Models\User;
use App\Queries\CustomerDashboardQuery;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(DashboardController::class, CustomerDashboardQuery::class);

uses()->group('account');

test('dashboard requires authentication', function () {
    get(route('account.dashboard'))
        ->assertRedirect(route('account.login'));
});

test('dashboard renders scoped stats and recent orders', function () {
    $user = User::factory()->create();
    Order::factory()->fulfilled()->count(2)->create(['customer_id' => $user->id]);
    Order::factory()->unfulfilled()->create(['customer_id' => $user->id]);
    Order::factory()->create(['customer_id' => User::factory()->create()->id]);

    actingAs($user)
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/account/dashboard')
            ->where('stats.total', 3)
            ->where('stats.fulfilled', 2)
            ->where('stats.unfulfilled', 1)
            ->has('recentOrders', 3)
            ->has('defaultAddress'));
});
