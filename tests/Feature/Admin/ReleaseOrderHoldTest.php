<?php

declare(strict_types=1);

use App\Actions\StoreOrderActivityAction;
use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ReleaseOrderHoldController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(ReleaseOrderHoldController::class, TransitionFulfillmentStatusAction::class, StoreOrderActivityAction::class);

uses()->group('orders');

test('on-hold order can be released', function () {
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::OnHold,
    ]);

    actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.release-hold', $order))
        ->assertRedirect(route('admin.orders.show', $order));

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
    ]);
});

test('on-hold order with existing shipments releases to in progress', function () {
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::OnHold,
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);

    actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.release-hold', $order))
        ->assertRedirect(route('admin.orders.show', $order));

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'fulfillment_status' => FulfillmentStatus::InProgress->value,
    ]);
});

test('records activity when releasing hold', function () {
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::OnHold,
    ]);

    actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.release-hold', $order));

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => OrderActivityType::FulfillmentStatusChanged->value,
    ]);
});

test('unfulfilled order cannot be released', function () {
    $order = Order::factory()->unfulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.release-hold', $order))
        ->assertStatus(409);
});

test('fulfilled order cannot be released', function () {
    $order = Order::factory()->fulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.release-hold', $order))
        ->assertStatus(409);
});

test('canceled order cannot be released', function () {
    $order = Order::factory()->canceled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.release-hold', $order))
        ->assertStatus(409);
});

test('requires authentication', function () {
    $order = Order::factory()->create();

    post(route('admin.orders.release-hold', $order))
        ->assertRedirect(route('admin.login'));
});

test('requires orders.update permission', function () {
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::OnHold,
    ]);

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::OrdersManage);

    actingAsAdmin()
        ->post(route('admin.orders.release-hold', $order))
        ->assertForbidden();
});
