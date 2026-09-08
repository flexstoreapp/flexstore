<?php

declare(strict_types=1);

use App\Actions\CancelOrderAction;
use App\Actions\ResendOrderNotificationAction;
use App\Actions\TransitionFulfillmentStatusAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\OrderEmailType;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\AdminCancelOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminHoldOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminInProgressOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminReleaseOrderHoldController;
use App\Http\Controllers\Api\V1\Admin\AdminResendOrderNotificationController;
use App\Http\Resources\Api\V1\Admin\AdminOrderStatusResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Payment\PaymentManager;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

covers(
    AdminCancelOrderController::class,
    AdminHoldOrderController::class,
    AdminInProgressOrderController::class,
    AdminReleaseOrderHoldController::class,
    AdminResendOrderNotificationController::class,
    AdminOrderStatusResource::class,
    CancelOrderAction::class,
    TransitionFulfillmentStatusAction::class,
    ResendOrderNotificationAction::class,
);

uses()->group('api', 'admin-api');

beforeEach(function (): void {
    PaymentManager::fake();
});

test('an order is canceled', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersCancel]));

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'requires_shipping' => true]);

    postJson('/api/v1/admin/orders/' . $order->id . '/cancel', ['reason' => 'customer_request'])
        ->assertOk()
        ->assertJsonPath('id', $order->id)
        ->assertJsonPath('is_canceled', true)
        ->assertJsonPath('cancellation_reason', 'customer_request')
        ->assertJsonPath('payment_status', PaymentStatus::Canceled->value);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => OrderActivityType::OrderCanceled->value,
    ]);
});

test('canceling without a reason fails validation', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersCancel]));

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'requires_shipping' => true]);

    postJson('/api/v1/admin/orders/' . $order->id . '/cancel', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

test('canceling an already canceled order conflicts', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersCancel]));

    $order = Order::factory()->canceled()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/cancel', ['reason' => 'other'])
        ->assertStatus(409);
});

test('canceling requires the orders cancel permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'requires_shipping' => true]);

    postJson('/api/v1/admin/orders/' . $order->id . '/cancel', ['reason' => 'other'])
        ->assertForbidden();
});

test('an unfulfilled order is put on hold', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'requires_shipping' => true]);

    postJson('/api/v1/admin/orders/' . $order->id . '/hold')
        ->assertOk()
        ->assertJsonPath('fulfillment_status', FulfillmentStatus::OnHold->value);

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'fulfillment_status' => FulfillmentStatus::OnHold->value,
    ]);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => OrderActivityType::FulfillmentStatusChanged->value,
    ]);
});

test('an order without unfulfilled items cannot be put on hold', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->unfulfilled()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/hold')->assertStatus(409);
});

test('a canceled order cannot be put on hold', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->canceled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    postJson('/api/v1/admin/orders/' . $order->id . '/hold')->assertStatus(409);
});

test('a held order is released back to unfulfilled', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::OnHold]);
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    postJson('/api/v1/admin/orders/' . $order->id . '/release-hold')
        ->assertOk()
        ->assertJsonPath('fulfillment_status', FulfillmentStatus::Unfulfilled->value);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => OrderActivityType::FulfillmentStatusChanged->value,
    ]);
});

test('a held order with shipments is released to in progress', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::OnHold]);
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1]);
    OrderShipment::factory()->create(['order_id' => $order->id]);

    postJson('/api/v1/admin/orders/' . $order->id . '/release-hold')
        ->assertOk()
        ->assertJsonPath('fulfillment_status', FulfillmentStatus::InProgress->value);
});

test('an order that is not on hold cannot be released', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'requires_shipping' => true]);

    postJson('/api/v1/admin/orders/' . $order->id . '/release-hold')->assertStatus(409);
});

test('an unfulfilled order is moved to in progress', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'requires_shipping' => true]);

    postJson('/api/v1/admin/orders/' . $order->id . '/in-progress')
        ->assertOk()
        ->assertJsonPath('fulfillment_status', FulfillmentStatus::InProgress->value);

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'fulfillment_status' => FulfillmentStatus::InProgress->value,
    ]);
});

test('an in progress order cannot be moved to in progress again', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->inProgress()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    postJson('/api/v1/admin/orders/' . $order->id . '/in-progress')->assertStatus(409);
});

test('a status transition requires the orders manage permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'requires_shipping' => true]);

    postJson('/api/v1/admin/orders/' . $order->id . '/hold')->assertForbidden();
});

test('an order confirmation email is resent', function (): void {
    Notification::fake();
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/resend-notification', [
        'type' => OrderEmailType::OrderConfirmed->value,
    ])->assertNoContent();

    Notification::assertSentTo($order->customer, CustomerOrderConfirmedNotification::class);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => OrderActivityType::EmailResent->value,
    ]);
});

test('resending a fulfillment email requires a shipment', function (): void {
    Notification::fake();
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/resend-notification', [
        'type' => OrderEmailType::OrderFulfilled->value,
    ])->assertUnprocessable()->assertJsonValidationErrors('shipment_id');
});

test('resending a notification requires the orders manage permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/resend-notification', [
        'type' => OrderEmailType::OrderConfirmed->value,
    ])->assertForbidden();
});
