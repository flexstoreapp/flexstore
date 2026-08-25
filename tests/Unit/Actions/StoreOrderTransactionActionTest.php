<?php

declare(strict_types=1);

use App\Actions\StoreOrderTransactionAction;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;

covers(StoreOrderTransactionAction::class);

uses()->group('actions', 'payment');

test('creates transaction with all fields', function () {
    $gateway = PaymentGateway::factory()->stripe()->create();
    $order = Order::factory()->for($gateway, 'paymentGateway')->create(['total' => '100.0000']);

    $action = app(StoreOrderTransactionAction::class);
    $transaction = $action->handle(
        order: $order,
        type: TransactionType::Sale,
        status: TransactionStatus::Success,
        amount: '100.0000',
        gatewayReference: 'pi_test_123',
        paymentMethod: 'card',
        paymentMethodDetails: ['brand' => 'visa', 'last4' => '4242'],
        failureReason: null,
        metadata: ['source' => 'checkout'],
    );

    expect($transaction)->toBeInstanceOf(OrderTransaction::class)
        ->and($transaction->order_id)->toBe($order->id)
        ->and($transaction->type)->toBe(TransactionType::Sale)
        ->and($transaction->status)->toBe(TransactionStatus::Success)
        ->and($transaction->amount)->toBe('100.0000')
        ->and($transaction->currency_code)->toBe($order->currency_code)
        ->and($transaction->payment_gateway_id)->toBe($gateway->id)
        ->and($transaction->gateway_reference)->toBe('pi_test_123')
        ->and($transaction->payment_method)->toBe('card')
        ->and($transaction->payment_method_details)->toBe(['brand' => 'visa', 'last4' => '4242'])
        ->and($transaction->metadata)->toBe(['source' => 'checkout']);
});

test('creates transaction with nullable fields omitted', function () {
    $order = Order::factory()->create();

    $action = app(StoreOrderTransactionAction::class);
    $transaction = $action->handle(
        order: $order,
        type: TransactionType::Sale,
        status: TransactionStatus::Success,
        amount: '50.0000',
    );

    expect($transaction->order_refund_id)->toBeNull()
        ->and($transaction->gateway_reference)->toBeNull()
        ->and($transaction->payment_method)->toBeNull()
        ->and($transaction->payment_method_details)->toBeNull()
        ->and($transaction->failure_reason)->toBeNull()
        ->and($transaction->metadata)->toBeNull();
});

test('creates failed transaction with failure reason', function () {
    $order = Order::factory()->create();

    $action = app(StoreOrderTransactionAction::class);
    $transaction = $action->handle(
        order: $order,
        type: TransactionType::Sale,
        status: TransactionStatus::Failed,
        amount: '75.0000',
        failureReason: 'Insufficient funds',
    );

    expect($transaction->status)->toBe(TransactionStatus::Failed)
        ->and($transaction->failure_reason)->toBe('Insufficient funds');
});

test('links transaction to refund', function () {
    $order = Order::factory()->create(['payment_status' => App\Enums\PaymentStatus::Paid]);
    $refund = OrderRefund::factory()->for($order)->create();

    $action = app(StoreOrderTransactionAction::class);
    $transaction = $action->handle(
        order: $order,
        type: TransactionType::Refund,
        status: TransactionStatus::Success,
        amount: '25.0000',
        refund: $refund,
    );

    expect($transaction->order_refund_id)->toBe($refund->id);
});

test('resolves payment gateway from order when not explicitly provided', function () {
    $gateway = PaymentGateway::factory()->cod()->create();
    $order = Order::factory()->for($gateway, 'paymentGateway')->create();

    $action = app(StoreOrderTransactionAction::class);
    $transaction = $action->handle(
        order: $order,
        type: TransactionType::Sale,
        status: TransactionStatus::Success,
        amount: '50.0000',
    );

    expect($transaction->payment_gateway_id)->toBe($gateway->id);
});

test('uses explicit payment gateway id when provided', function () {
    $orderGateway = PaymentGateway::factory()->cod()->create();
    $otherGateway = PaymentGateway::factory()->stripe()->create();
    $order = Order::factory()->for($orderGateway, 'paymentGateway')->create();

    $action = app(StoreOrderTransactionAction::class);
    $transaction = $action->handle(
        order: $order,
        type: TransactionType::Sale,
        status: TransactionStatus::Success,
        amount: '50.0000',
        paymentGatewayId: $otherGateway->id,
    );

    expect($transaction->payment_gateway_id)->toBe($otherGateway->id);
});
