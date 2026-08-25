<?php

declare(strict_types=1);

use App\Actions\ReconcileOrderFinancialsAction;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\OrderTransaction;

covers(ReconcileOrderFinancialsAction::class);

uses()->group('actions', 'reconciliation');

test('unpaid order has balance_due equal to total', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('0.0000')
        ->and($result->net_paid_total)->toBe('0.0000')
        ->and($result->balance_due_total)->toBe('100.0000')
        ->and($result->credit_due_total)->toBe('0.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('paid order with matching total is settled', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('100.0000')
        ->and($result->net_paid_total)->toBe('100.0000')
        ->and($result->balance_due_total)->toBe('0.0000')
        ->and($result->credit_due_total)->toBe('0.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::Paid);
});

test('paid order edited upward becomes partially paid', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '130.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('100.0000')
        ->and($result->net_paid_total)->toBe('100.0000')
        ->and($result->balance_due_total)->toBe('30.0000')
        ->and($result->credit_due_total)->toBe('0.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::PartiallyPaid);
});

test('paid order edited downward has credit due', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '80.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('100.0000')
        ->and($result->net_paid_total)->toBe('100.0000')
        ->and($result->balance_due_total)->toBe('0.0000')
        ->and($result->credit_due_total)->toBe('20.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::Paid);
});

test('partially refunded order computes correct net paid', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::PartiallyRefunded,
        'total' => '100.0000',
        'refund_total' => '30.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('100.0000')
        ->and($result->net_paid_total)->toBe('70.0000')
        ->and($result->balance_due_total)->toBe('0.0000')
        ->and($result->credit_due_total)->toBe('0.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
});

test('multiple successful transactions are summed', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '150.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '50.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('150.0000')
        ->and($result->net_paid_total)->toBe('150.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::Paid);
});

test('failed transactions are not counted', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Failed,
        'amount' => '100.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('0.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('fully voided payments derive unpaid status', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Void,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->paid_total)->toBe('0.0000')
        ->and($result->net_paid_total)->toBe('0.0000')
        ->and($result->balance_due_total)->toBe('100.0000')
        ->and($result->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('recalculates customer lifetime value when payment status changes', function () {
    $order = Order::factory()->fulfilled()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'total' => '100.0000',
        'refund_total' => '0.0000',
        'exchange_rate' => '1.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $result = app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($result->payment_status)->toBe(PaymentStatus::Paid);

    $customer = $order->customer->fresh();
    expect($customer->lifetime_value)->toBe('100.0000');
});

test('does not recalculate customer lifetime value when payment status is unchanged', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $customer = $order->customer;
    $customer->update(['lifetime_value' => '999.0000']);

    app(ReconcileOrderFinancialsAction::class)->handle($order);

    expect($customer->fresh()->lifetime_value)->toBe('999.0000');
});

test('reconciliation is idempotent', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    OrderTransaction::factory()->create([
        'order_id' => $order->id,
        'type' => TransactionType::Sale,
        'status' => TransactionStatus::Success,
        'amount' => '100.0000',
    ]);

    $action = app(ReconcileOrderFinancialsAction::class);

    $first = $action->handle($order);
    $second = $action->handle($first);

    expect($second->paid_total)->toBe($first->paid_total)
        ->and($second->net_paid_total)->toBe($first->net_paid_total)
        ->and($second->balance_due_total)->toBe($first->balance_due_total)
        ->and($second->credit_due_total)->toBe($first->credit_due_total)
        ->and($second->payment_status)->toBe($first->payment_status);
});
