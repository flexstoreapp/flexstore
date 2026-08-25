<?php

declare(strict_types=1);

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Models\PaymentSession;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(OrderTransaction::class);

uses()->group('models', 'order');

test('has factory', function () {
    expect(OrderTransaction::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $transaction = new OrderTransaction();
    $casts = $transaction->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('type', TransactionType::class)
        ->toHaveKey('status', TransactionStatus::class)
        ->toHaveKey('amount', 'decimal:4')
        ->toHaveKey('payment_method_details', 'array')
        ->toHaveKey('is_manual_entry', 'boolean')
        ->toHaveKey('metadata', 'array');
});

test('casts type to TransactionType enum', function () {
    $transaction = OrderTransaction::factory()->sale()->create();

    expect($transaction->type)->toBeInstanceOf(TransactionType::class)
        ->and($transaction->type)->toBe(TransactionType::Sale);
});

test('casts status to TransactionStatus enum', function () {
    $transaction = OrderTransaction::factory()->successful()->create();

    expect($transaction->status)->toBeInstanceOf(TransactionStatus::class)
        ->and($transaction->status)->toBe(TransactionStatus::Success);
});

test('casts amount to decimal', function () {
    $transaction = OrderTransaction::factory()->create(['amount' => '99.9900']);

    expect($transaction->amount)->toBe('99.9900');
});

test('belongs to payment gateway relationship', function () {
    $gateway = PaymentGateway::factory()->create();
    $transaction = OrderTransaction::factory()->create([
        'payment_gateway_id' => $gateway->id,
    ]);

    expect($transaction->paymentGateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($transaction->paymentGateway->id)->toBe($gateway->id);
});

test('belongs to payment session relationship', function () {
    $session = PaymentSession::factory()->create();
    $transaction = OrderTransaction::factory()->create([
        'payment_session_id' => $session->id,
    ]);

    expect($transaction->paymentSession)->toBeInstanceOf(PaymentSession::class)
        ->and($transaction->paymentSession->id)->toBe($session->id);
});

test('belongs to related transaction relationship', function () {
    $original = OrderTransaction::factory()->sale()->successful()->create();
    $refund = OrderTransaction::factory()->refund()->successful()->create([
        'order_id' => $original->order_id,
        'related_transaction_id' => $original->id,
    ]);

    expect($refund->relatedTransaction)->toBeInstanceOf(OrderTransaction::class)
        ->and($refund->relatedTransaction->id)->toBe($original->id);
});
