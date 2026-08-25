<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\OrderController;
use App\Http\Requests\Storefront\ShowAccountOrdersRequest;
use App\Models\Order;
use App\Models\User;
use App\Queries\CustomerOrderListQuery;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(OrderController::class, CustomerOrderListQuery::class, ShowAccountOrdersRequest::class);

uses()->group('account');

test('orders list requires authentication', function () {
    get(route('account.orders.index'))
        ->assertRedirect(route('account.login'));
});

test('orders list page renders only the customer own orders', function () {
    $user = User::factory()->create();
    Order::factory()->count(2)->create(['customer_id' => $user->id]);
    Order::factory()->create(['customer_id' => User::factory()->create()->id]);

    actingAs($user)
        ->get(route('account.orders.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/account/orders/list')
            ->has('orders.data', 2)
            ->where('status', null));
});

test('orders list filters by fulfillment status', function () {
    $user = User::factory()->create();
    Order::factory()->fulfilled()->create(['customer_id' => $user->id]);
    Order::factory()->unfulfilled()->create(['customer_id' => $user->id]);

    actingAs($user)
        ->get(route('account.orders.index', ['status' => 'fulfilled']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('orders.data', 1)
            ->where('status', 'fulfilled'));
});

test('orders list rejects an invalid status filter', function () {
    actingAs(User::factory()->create())
        ->get(route('account.orders.index', ['status' => 'bogus']))
        ->assertSessionHasErrors('status');
});
