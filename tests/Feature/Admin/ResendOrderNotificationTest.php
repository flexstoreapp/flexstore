<?php

declare(strict_types=1);

use App\Actions\ResendOrderNotificationAction;
use App\Enums\OrderActivityType;
use App\Enums\Permission;
use App\Http\Controllers\Admin\ResendOrderNotificationController;
use App\Http\Requests\Admin\ResendOrderNotificationRequest;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderRefund;
use App\Models\OrderShipment;
use App\Models\User;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Notifications\CustomerOrderFulfilledNotification;
use App\Notifications\CustomerOrderRefundedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers([
    ResendOrderNotificationController::class,
    ResendOrderNotificationRequest::class,
    ResendOrderNotificationAction::class,
]);

uses()->group('order', 'notification');

function actingAsWithUpdatePermission(): User
{
    $role = Role::query()->firstOrCreate(['name' => 'test-update']);
    $perm = SpatiePermission::query()->firstOrCreate(['name' => Permission::OrdersManage]);
    $role->syncPermissions([$perm]);

    $user = User::factory()->create();
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}

test('admin can resend the order confirmation email', function () {
    Notification::fake();
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();

    $response = post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_confirmed',
    ]);

    $response->assertRedirect();

    Notification::assertSentTo($order->customer, CustomerOrderConfirmedNotification::class);

    $activity = OrderActivity::query()->where('order_id', $order->id)->where('type', OrderActivityType::EmailResent)->first();
    expect($activity)->not->toBeNull();
    expect($activity->metadata['email_type'])->toBe('order_confirmed');
});

test('admin can resend the shipping confirmation for a shipment', function () {
    Notification::fake();
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    $response = post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_fulfilled',
        'shipment_id' => $shipment->id,
    ]);

    $response->assertRedirect();

    Notification::assertSentTo($order->customer, CustomerOrderFulfilledNotification::class);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => OrderActivityType::EmailResent->value,
    ]);
});

test('admin can resend the refund email for a completed refund', function () {
    Notification::fake();
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();
    $refund = OrderRefund::factory()->completed()->create(['order_id' => $order->id]);

    $response = post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_refunded',
        'refund_id' => $refund->id,
    ]);

    $response->assertRedirect();

    Notification::assertSentTo($order->customer, CustomerOrderRefundedNotification::class);
});

test('resending to a guest order sends an on-demand notification', function () {
    Notification::fake();
    actingAsWithUpdatePermission();

    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_confirmed',
    ])->assertRedirect();

    Notification::assertSentOnDemand(CustomerOrderConfirmedNotification::class);
});

test('type is required', function () {
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();

    post(route('admin.orders.resend-notification', $order), [])
        ->assertSessionHasErrors('type');
});

test('rejects an unknown email type', function () {
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();

    post(route('admin.orders.resend-notification', $order), [
        'type' => 'something_else',
    ])->assertSessionHasErrors('type');
});

test('shipment id is required for shipment email types', function () {
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();

    post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_fulfilled',
    ])->assertSessionHasErrors('shipment_id');
});

test('refund id is required for the refund email type', function () {
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();

    post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_refunded',
    ])->assertSessionHasErrors('refund_id');
});

test('rejects a shipment belonging to another order', function () {
    actingAsWithUpdatePermission();

    $order = Order::factory()->create();
    $otherShipment = OrderShipment::factory()->create(['order_id' => Order::factory()->create()->id]);

    post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_fulfilled',
        'shipment_id' => $otherShipment->id,
    ])->assertSessionHasErrors('shipment_id');
});

test('admin without permission gets 403', function () {
    Notification::fake();

    $role = Role::query()->firstOrCreate(['name' => 'no-perms']);
    $user = User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);

    $order = Order::factory()->create();

    post(route('admin.orders.resend-notification', $order), [
        'type' => 'order_confirmed',
    ])->assertForbidden();

    Notification::assertNothingSent();
});
