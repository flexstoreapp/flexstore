<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Payment\RefundAllocator;

covers(RefundAllocator::class);

uses()->group('payment', 'refund');

test('allocates against single transaction', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'pi_100',
    ]);

    $allocations = (new RefundAllocator)->allocate($order, '30.0000');

    expect($allocations)->toHaveCount(1)
        ->and($allocations[0]->amount)->toBe('30.0000')
        ->and($allocations[0]->gatewayReference)->toBe('pi_100');
});

test('allocates across multiple transactions oldest first', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '60.0000',
        'gateway_reference' => 'pi_first',
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '40.0000',
        'gateway_reference' => 'pi_second',
    ]);

    $allocations = (new RefundAllocator)->allocate($order, '50.0000');

    expect($allocations)->toHaveCount(1)
        ->and($allocations[0]->gatewayReference)->toBe('pi_first')
        ->and($allocations[0]->amount)->toBe('50.0000');
});

test('respects already refunded amounts', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PartiallyRefunded]);

    $sale = OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'pi_100',
    ]);

    OrderTransaction::factory()->refund()->successful()->create([
        'order_id' => $order->id,
        'amount' => '40.0000',
        'related_transaction_id' => $sale->id,
    ]);

    $allocations = (new RefundAllocator)->allocate($order, '50.0000');

    expect($allocations)->toHaveCount(1)
        ->and($allocations[0]->amount)->toBe('50.0000');
});

test('accounts for pending refund transactions', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);

    $sale = OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'pi_100',
    ]);

    OrderTransaction::factory()->refund()->create([
        'order_id' => $order->id,
        'amount' => '40.0000',
        'status' => TransactionStatus::Pending,
        'related_transaction_id' => $sale->id,
    ]);

    $allocations = (new RefundAllocator)->allocate($order, '80.0000');

    expect($allocations)->toHaveCount(1)
        ->and($allocations[0]->amount)->toBe('60.0000');
});

test('returns empty when no refundable transactions', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Unpaid]);

    $allocations = (new RefundAllocator)->allocate($order, '50.0000');

    expect($allocations)->toBeEmpty();
});
