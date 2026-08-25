<?php

declare(strict_types=1);

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Requests\Admin\IndexAdminOrderRequest;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Queries\OrderListQuery;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(OrderController::class, OrderListQuery::class, IndexAdminOrderRequest::class);

uses()->group('order');

test('displays order list page', function () {
    Order::factory(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.orders.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 3)
                ->where('filters.query', null)
                ->where('filters.fulfillment_status', null)
                ->where('filters.payment_status', null)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('can filter orders by customer email', function () {
    $order1 = Order::factory()->create([
        'customer_email' => 'john@example.com',
    ]);
    $order2 = Order::factory()->create([
        'customer_email' => 'jane@example.com',
    ]);
    $order3 = Order::factory()->create([
        'customer_email' => 'bob@test.com',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['query' => 'john@example.com']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $order1->id)
                ->where('orders.data.0.customer_email', 'john@example.com')
        );
});

test('can filter orders by billing address name', function () {
    $order1 = Order::factory()->create(['customer_email' => 'order1@example.test']);
    OrderAddress::factory()->billing()->forOrder($order1)->create(['first_name' => 'John', 'last_name' => 'Doe']);

    $order2 = Order::factory()->create(['customer_email' => 'order2@example.test']);
    OrderAddress::factory()->billing()->forOrder($order2)->create(['first_name' => 'Jane', 'last_name' => 'Smith']);

    $order3 = Order::factory()->create(['customer_email' => 'order3@example.test']);
    OrderAddress::factory()->billing()->forOrder($order3)->create(['first_name' => 'Johnny', 'last_name' => 'Johnson']);

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['query' => 'John']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 2)
                ->has(
                    'orders.data',
                    fn ($orders) => $orders
                        ->where('0.id', $order1->id)
                        ->where('1.id', $order3->id)
                )
        );
});

test('can filter orders by partial customer name', function () {
    $order1 = Order::factory()->create(['customer_email' => 'order1@example.test']);
    OrderAddress::factory()->billing()->forOrder($order1)->create(['first_name' => 'Alex', 'last_name' => 'Smith']);

    $order2 = Order::factory()->create(['customer_email' => 'order2@example.test']);
    OrderAddress::factory()->billing()->forOrder($order2)->create(['first_name' => 'Alexander', 'last_name' => 'Jones']);

    Order::factory()->create(['customer_email' => 'order3@example.test']);

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['query' => 'Alex']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 2)
                ->has('orders.data.0')
                ->has('orders.data.1')
        );
});

test('can filter orders by fulfillment status', function () {
    Order::factory()->unfulfilled()->create();
    Order::factory()->inProgress()->create();
    Order::factory()->fulfilled()->create();
    Order::factory()->unfulfilled()->create();

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['fulfillment_status' => FulfillmentStatus::Unfulfilled->value]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 2)
                ->has('orders.data.0')
                ->has('orders.data.1')
        );
});

test('can filter orders by payment status', function () {
    $order1 = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $order2 = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
    ]);

    $order3 = Order::factory()->create([
        'payment_status' => PaymentStatus::Failed,
    ]);

    $order4 = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['payment_status' => PaymentStatus::Paid->value]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 2)
                ->has('orders.data.0')
                ->has('orders.data.1')
        );
});

test('returns all orders when no filters are applied', function () {
    Order::factory()->count(5)->create();

    $response = actingAsSuperAdmin()->get(route('admin.orders.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 5)
        );
});

test('returns empty result when no orders match filters', function () {
    Order::factory()->unfulfilled()->create();
    Order::factory()->inProgress()->create();

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['query' => 'NonExistentCustomer']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 0)
        );

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['fulfillment_status' => FulfillmentStatus::Fulfilled->value]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 0)
        );
});

test('can filter orders by id with # prefix', function () {
    $order1 = Order::factory()->create();
    $order2 = Order::factory()->create();
    $order3 = Order::factory()->create();

    $response = actingAsSuperAdmin()->get(route('admin.orders.index', ['query' => "#{$order2->id}"]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/orders/list')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $order2->id)
        );
});

test('requires authentication', function () {
    Order::factory()->count(2)->create();

    $response = get(route('admin.orders.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires orders.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    Order::factory()->count(2)->create();

    $response = actingAsAdmin()->get(route('admin.orders.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::OrdersView);

    $response = actingAsAdmin()->get(route('admin.orders.index'));

    $response->assertForbidden();
});
