<?php

declare(strict_types=1);

use App\Actions\HandleExternalRefundAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderRefund;
use App\Models\OrderTransaction;
use App\Models\User;
use App\Payment\PaymentManager;

covers(HandleExternalRefundAction::class);

uses()->group('actions', 'refund');

beforeEach(function () {
    PaymentManager::fake();
});

test('creates refund record with correct attributes', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('25.0000');

    $refund = OrderRefund::query()->where('order_id', $order->id)->first();
    expect($refund->amount)->toBe('25.0000')
        ->and($refund->status)->toBe(RefundStatus::Completed)
        ->and($refund->reason)->toBe('Refund initiated from payment gateway.')
        ->and($refund->items)->toHaveCount(0);
});

test('does not change fulfillment status', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '50.0000');

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('is idempotent when same cumulative total is sent twice', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    $action = app(HandleExternalRefundAction::class);
    $action->handle($order, '25.0000');
    $action->handle($order, '25.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('25.0000');
    expect(OrderRefund::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('handles incremental cumulative totals creating two refund records', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    $action = app(HandleExternalRefundAction::class);
    $action->handle($order, '25.0000');
    $action->handle($order, '50.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('50.0000');

    $refunds = OrderRefund::query()->where('order_id', $order->id)->orderBy('created_at')->get();
    expect($refunds)->toHaveCount(2)
        ->and($refunds[0]->amount)->toBe('25.0000')
        ->and($refunds[1]->amount)->toBe('25.0000');
});

test('PAID → partial refund → PARTIALLY_REFUNDED', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($order->refund_total)->toBe('25.0000');
});

test('PAID → full refund → REFUNDED', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '100.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($order->refund_total)->toBe('100.0000');
});

test('PARTIALLY_REFUNDED → additional refund → stays PARTIALLY_REFUNDED', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '25.0000',
        'payment_status' => PaymentStatus::PartiallyRefunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '50.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($order->refund_total)->toBe('50.0000');
});

test('PARTIALLY_REFUNDED → remaining refunded → REFUNDED', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '25.0000',
        'payment_status' => PaymentStatus::PartiallyRefunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '100.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($order->refund_total)->toBe('100.0000');
});

test('PARTIALLY_REFUNDED → partial reversal → stays PARTIALLY_REFUNDED', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '50.0000',
        'payment_status' => PaymentStatus::PartiallyRefunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($order->refund_total)->toBe('25.0000');
});

test('PARTIALLY_REFUNDED → all canceled → PAID', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '25.0000',
        'payment_status' => PaymentStatus::PartiallyRefunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '0.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->refund_total)->toBe('0.0000');
});

test('REFUNDED → partial reversal → PARTIALLY_REFUNDED', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '100.0000',
        'payment_status' => PaymentStatus::Refunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '50.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($order->refund_total)->toBe('50.0000');
});

test('REFUNDED → all canceled → PAID', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '100.0000',
        'payment_status' => PaymentStatus::Refunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '0.0000');

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->refund_total)->toBe('0.0000');
});

test('UNPAID → refund increases refund_total but preserves status', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '10.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('10.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('FAILED → refund increases refund_total but preserves status', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Failed,
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '10.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('10.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::Failed);
});

test('CANCELED → refund increases refund_total but preserves status', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Canceled,
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '10.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('10.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::Canceled);
});

test('reversal creates failed refund record with correct reason', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '50.0000',
        'payment_status' => PaymentStatus::PartiallyRefunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000');

    $refund = OrderRefund::query()->where('order_id', $order->id)->first();
    expect($refund->status)->toBe(RefundStatus::Failed)
        ->and($refund->amount)->toBe('25.0000')
        ->and($refund->reason)->toBe('Refund canceled by payment provider.');
});

test('does not record payment activity for external refund', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000');

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::PaymentStatusChanged)
        ->first();

    expect($activity)->toBeNull();
});

test('does not record payment status activity when status is unchanged', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '25.0000',
        'payment_status' => PaymentStatus::PartiallyRefunded,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '50.0000');

    expect(OrderActivity::query()->where('order_id', $order->id)->where('type', OrderActivityType::PaymentStatusChanged)->count())->toBe(0);
});

test('does not record payment status activity for non-transitionable statuses', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000');

    expect(OrderActivity::query()->where('order_id', $order->id)->where('type', OrderActivityType::PaymentStatusChanged)->count())->toBe(0);
});

test('recalculates customer lifetime value', function () {
    $customer = User::factory()->customer()->create();
    $order = Order::factory()->fulfilled()->create([
        'customer_id' => $customer->id,
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'exchange_rate' => '1.0000',
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '40.0000');

    $customer->refresh();
    expect($customer->lifetime_value)->toBe('60.0000');
});

test('handles order without customer', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
        'customer_id' => null,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('25.0000');
    expect(OrderRefund::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('caps cumulative refund total to order total', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '150.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('100.0000');

    $refund = OrderRefund::query()->where('order_id', $order->id)->first();
    expect($refund->amount)->toBe('100.0000');
});

test('stores gateway reference on transaction', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '25.0000', 'tr_MollieRef123');

    $transaction = OrderTransaction::query()->where('order_id', $order->id)->latest('id')->first();
    expect($transaction->gateway_reference)->toBe('tr_MollieRef123');
});

test('ignores negative cumulative refund total', function () {
    $order = Order::factory()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    app(HandleExternalRefundAction::class)->handle($order, '-10.00');

    $order->refresh();
    expect($order->refund_total)->toBe('0.0000');
    expect(OrderRefund::query()->where('order_id', $order->id)->count())->toBe(0);
});
