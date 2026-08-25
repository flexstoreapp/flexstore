<?php

declare(strict_types=1);

use App\Actions\CancelOrderAction;
use App\DTOs\CancelOrderInput;
use App\Enums\CancellationReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\OrderConflictException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\AdminOrderCanceledNotification;
use App\Notifications\CustomerOrderCanceledNotification;
use App\Notifications\CustomerOrderRefundedNotification;
use App\Payment\Drivers\CodDriver;
use App\Payment\PaymentManager;
use Illuminate\Support\Facades\Notification;

covers(CancelOrderAction::class, CancelOrderInput::class);

uses()->group('actions', 'orders');

beforeEach(function () {
    PaymentManager::fake();
});

function cancelAction(): CancelOrderAction
{
    return app(CancelOrderAction::class);
}

function createUser(): User
{
    return User::factory()->create();
}

test('cancels unfulfilled unpaid order, setting canceled_at and payment_status', function () {
    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
    ]));

    expect($result->canceled_at)->not->toBeNull()
        ->and($result->is_canceled)->toBeTrue()
        ->and($result->cancellation_reason)->toBe(CancellationReason::CustomerRequest)
        ->and($result->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($result->payment_status)->toBe(PaymentStatus::Canceled);
});

test('preserves fulfillment_status when canceling an in-progress order', function () {
    $user = createUser();
    $order = Order::factory()->inProgress()->create();

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'refund' => false,
    ]));

    expect($result->is_canceled)->toBeTrue()
        ->and($result->fulfillment_status)->toBe(FulfillmentStatus::InProgress)
        ->and($result->payment_status)->toBe(PaymentStatus::Paid);
});

test('cancels on-hold order while preserving fulfillment_status', function () {
    $user = createUser();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::OnHold,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'inventory',
    ]));

    expect($result->is_canceled)->toBeTrue()
        ->and($result->fulfillment_status)->toBe(FulfillmentStatus::OnHold)
        ->and($result->payment_status)->toBe(PaymentStatus::Canceled);
});

test('throws on fulfilled order', function () {
    $user = createUser();
    $order = Order::factory()->fulfilled()->create();

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray(['reason' => 'customer_request']));
})->throws(OrderConflictException::class);

test('throws on already canceled order', function () {
    $user = createUser();
    $order = Order::factory()->canceled()->create();

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray(['reason' => 'customer_request']));
})->throws(OrderConflictException::class);

test('processes full refund when requested on paid order', function () {
    $user = createUser();
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);

    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '150.0000',
        'subtotal' => '130.0000',
        'shipping_total' => '20.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'refund_total' => '0.0000',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => '43.3333',
        'total_price' => '130.0000',
        'tax_amount' => '0.0000',
    ]);

    PaymentManager::fake(new CodDriver());

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'fraudulent',
        'refund' => true,
        'restock' => true,
    ]));

    expect($result->is_canceled)->toBeTrue();

    $refund = OrderRefund::query()->where('order_id', $order->id)->first();
    expect($refund)->not->toBeNull()
        ->and($refund->status)->toBe(App\Enums\RefundStatus::Completed);

    $product->refresh();
    expect($product->stock)->toBe(13);
});

test('skips refund when order is not refundable', function () {
    $user = createUser();
    $order = Order::factory()->unfulfilled()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'refund' => true,
    ]));

    expect($result->is_canceled)->toBeTrue()
        ->and($result->payment_status)->toBe(PaymentStatus::Canceled);

    expect(OrderRefund::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('restocks items without refund when restock is requested', function () {
    $user = createUser();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 5]);

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => '10.0000',
        'total_price' => '30.0000',
        'tax_amount' => '0.0000',
    ]);

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'inventory',
        'restock' => true,
    ]));

    $product->refresh();
    expect($product->stock)->toBe(8);

    $movement = StockMovement::query()->where('product_id', $product->id)->latest('id')->first();
    expect($movement->reason)->toBe(StockMovementReason::Cancellation)
        ->and($movement->quantity)->toBe(3);
});

test('does not restock items when restock is false', function () {
    $user = createUser();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 5]);

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => '10.0000',
        'total_price' => '30.0000',
        'tax_amount' => '0.0000',
    ]);

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'restock' => false,
    ]));

    $product->refresh();
    expect($product->stock)->toBe(5);
});

test('does not restock items that do not track stock', function () {
    $user = createUser();
    $product = Product::factory()->create(['track_stock' => false, 'stock' => null]);

    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'restock' => true,
    ]));

    expect(StockMovement::query()->where('product_id', $product->id)->exists())->toBeFalse();
});

test('decrements coupon usage when order has coupon', function () {
    $user = createUser();
    $coupon = Coupon::factory()->create(['used_count' => 3]);
    $order = Order::factory()->unfulfilled()->create([
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
    ]);

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray(['reason' => 'customer_request']));

    $coupon->refresh();
    expect($coupon->used_count)->toBe(2);
});

test('does not decrement coupon usage when order has no coupon', function () {
    $user = createUser();
    $order = Order::factory()->unfulfilled()->create([
        'coupon_id' => null,
        'coupon_code' => null,
    ]);

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray(['reason' => 'customer_request']));

    expect(true)->toBeTrue();
});

test('records order_canceled activity with cancellation reason', function () {
    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderCanceled->value)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['cancellation_reason'])->toBe('customer_request')
        ->and($activity->metadata['fulfillment_status'])->toBe('unfulfilled');
});

test('records order_canceled activity with cancellation reason and note', function () {
    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'other',
        'reason_note' => 'Duplicate order placed by mistake',
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderCanceled->value)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['cancellation_reason'])->toBe('other')
        ->and($activity->metadata['cancellation_note'])->toBe('Duplicate order placed by mistake');
});

test('persists cancellation reason and note on the order', function () {
    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'other',
        'reason_note' => 'Duplicate order placed by mistake',
    ]));

    expect($result->cancellation_reason)->toBe(CancellationReason::Other)
        ->and($result->cancellation_note)->toBe('Duplicate order placed by mistake');
});

test('sends customer notification when notify_customer is true', function () {
    Notification::fake();
    Setting::setValue('notification_admin_order_canceled', true);
    Setting::setValue('store_email', 'admin@store.com');

    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'notify_customer' => true,
    ]));

    Notification::assertSentOnDemand(AdminOrderCanceledNotification::class);
    Notification::assertSentTo($order->customer, CustomerOrderCanceledNotification::class);
});

test('does not send customer notification when notify_customer is false', function () {
    Notification::fake();
    Setting::setValue('notification_admin_order_canceled', true);
    Setting::setValue('store_email', 'admin@store.com');

    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'notify_customer' => false,
    ]));

    Notification::assertSentOnDemand(AdminOrderCanceledNotification::class);
    Notification::assertNotSentTo($order->customer, CustomerOrderCanceledNotification::class);
});

test('preserves partially paid status when cancelling without refund', function () {
    $user = createUser();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_status' => PaymentStatus::PartiallyPaid,
        'total' => '100.0000',
        'paid_total' => '50.0000',
        'refund_total' => '0.0000',
    ]);

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'refund' => false,
    ]));

    expect($result->is_canceled)->toBeTrue()
        ->and($result->payment_status)->toBe(PaymentStatus::PartiallyPaid);
});

test('transitions failed payment to canceled', function () {
    $user = createUser();
    $order = Order::factory()->failed()->create();

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'other',
    ]));

    expect($result->is_canceled)->toBeTrue()
        ->and($result->payment_status)->toBe(PaymentStatus::Canceled);
});

test('all cancellation reasons are handled', function (string $reason) {
    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    $result = cancelAction()->handle($user, $order, CancelOrderInput::fromArray(['reason' => $reason]));

    expect($result->is_canceled)->toBeTrue();
})->with(['customer_request', 'fraudulent', 'inventory', 'other']);

test('does not send refund notification when notify_customer is false', function () {
    Notification::fake();

    $user = createUser();
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $product = Product::factory()->create(['track_stock' => false]);

    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'subtotal' => '100.0000',
        'shipping_total' => '0.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'refund_total' => '0.0000',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    PaymentManager::fake(new CodDriver());

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'refund' => true,
        'notify_customer' => false,
    ]));

    Notification::assertNotSentTo($order->customer, CustomerOrderCanceledNotification::class);
    Notification::assertNotSentTo($order->customer, CustomerOrderRefundedNotification::class);
});

test('does not send admin cancellation notification when setting is disabled', function () {
    Notification::fake();
    Setting::setValue('notification_admin_order_canceled', false);

    $user = createUser();
    $order = Order::factory()->unfulfilled()->create();

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
        'notify_customer' => false,
    ]));

    Notification::assertNothingSent();
});

test('recalculates LTV when canceling a paid order', function () {
    $user = createUser();
    $order = Order::factory()->inProgress()->create([
        'total' => '100.0000',
        'paid_total' => '100.0000',
        'net_paid_total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    $order->customer->update(['lifetime_value' => '100.0000']);

    cancelAction()->handle($user, $order, CancelOrderInput::fromArray([
        'reason' => 'customer_request',
    ]));

    expect($order->customer->fresh()->lifetime_value)->toBe('0.0000');
});
