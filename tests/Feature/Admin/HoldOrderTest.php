<?php

declare(strict_types=1);

use App\Actions\StoreOrderActivityAction;
use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\HoldOrderController;
use App\Models\Order;
use App\Queries\OrderItemBreakdownQuery;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\post;

covers([
    HoldOrderController::class,
    TransitionFulfillmentStatusAction::class,
    StoreOrderActivityAction::class,
    OrderItemBreakdownQuery::class,
]);

uses()->group('orders');

test('order with no fulfillable items cannot be put on hold', function () {
    $order = Order::factory()->unfulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.hold', $order))
        ->assertStatus(409);
});

test('fulfilled order cannot be put on hold', function () {
    $order = Order::factory()->fulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.hold', $order))
        ->assertStatus(409);
});

test('canceled order cannot be put on hold', function () {
    $order = Order::factory()->canceled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.hold', $order))
        ->assertStatus(409);
});

test('requires authentication', function () {
    $order = Order::factory()->create();

    post(route('admin.orders.hold', $order))
        ->assertRedirect(route('admin.login'));
});

test('requires orders.update permission', function () {
    $order = Order::factory()->unfulfilled()->create();

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::OrdersManage);

    actingAsAdmin()
        ->post(route('admin.orders.hold', $order))
        ->assertForbidden();
});
