<?php

declare(strict_types=1);

use App\Actions\StoreOrderActivityAction;
use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\InProgressOrderController;
use App\Models\Order;
use App\Queries\OrderItemBreakdownQuery;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\post;

covers([
    InProgressOrderController::class,
    TransitionFulfillmentStatusAction::class,
    StoreOrderActivityAction::class,
    OrderItemBreakdownQuery::class,
]);

uses()->group('orders');

test('order with no fulfillable items cannot be marked as in progress', function () {
    $order = Order::factory()->unfulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.in-progress', $order))
        ->assertStatus(409);
});

test('in-progress order cannot be marked as in progress again', function () {
    $order = Order::factory()->inProgress()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.in-progress', $order))
        ->assertStatus(409);
});

test('fulfilled order cannot be marked as in progress', function () {
    $order = Order::factory()->fulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.in-progress', $order))
        ->assertStatus(409);
});

test('canceled order cannot be marked as in progress', function () {
    $order = Order::factory()->canceled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.in-progress', $order))
        ->assertStatus(409);
});

test('on-hold order cannot be marked as in progress', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::OnHold,
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.orders.in-progress', $order))
        ->assertStatus(409);
});

test('requires authentication', function () {
    $order = Order::factory()->create();

    post(route('admin.orders.in-progress', $order))
        ->assertRedirect(route('admin.login'));
});

test('requires orders.update permission', function () {
    $order = Order::factory()->unfulfilled()->create();

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::OrdersManage);

    actingAsAdmin()
        ->post(route('admin.orders.in-progress', $order))
        ->assertForbidden();
});
