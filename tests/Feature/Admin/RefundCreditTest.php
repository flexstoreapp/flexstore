<?php

declare(strict_types=1);

use App\Actions\RefundOrderCreditAction;
use App\Enums\PaymentStatus;
use App\Enums\RefundItemType;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Admin\OrderRefundCreditController;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Payment\PaymentManager;

covers(OrderRefundCreditController::class, RefundOrderCreditAction::class);

uses()->group('admin', 'orders', 'refund');

test('can refund credit difference', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '80.0000',
        'paid_total' => '100.0000',
        'net_paid_total' => '100.0000',
        'refund_total' => '0.0000',
        'credit_due_total' => '20.0000',
        'payment_gateway_id' => $gateway->id,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.orders.refund-credit.store', $order))
        ->assertRedirect();

    $refund = OrderRefund::query()->where('order_id', $order->id)->first();
    expect($refund)->not->toBeNull()
        ->and($refund->amount)->toBe('20.0000');

    $adjustmentItem = $refund->items()->where('type', RefundItemType::Adjustment)->first();
    expect($adjustmentItem)->not->toBeNull()
        ->and($adjustmentItem->amount)->toBe('20.0000');

    $order->refresh();
    expect($order->refund_total)->toBe('20.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
});

test('cannot refund when no credit owed', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'credit_due_total' => '0.0000',
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.orders.refund-credit.store', $order))
        ->assertStatus(422);
});

test('credit refund marks transaction as failed when gateway refund fails', function () {
    $failingDriver = new class() implements App\Payment\Contracts\PaymentDriver
    {
        public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): App\Payment\DTOs\VerificationResult
        {
            return new App\Payment\DTOs\VerificationResult(status: $currentStatus);
        }

        public function createSession(App\Payment\DTOs\CreateSession $session): App\Payment\DTOs\SessionResult
        {
            return new App\Payment\DTOs\SessionResult(
                status: PaymentStatus::Paid,
                redirectUrl: '',
            );
        }

        public function refund(App\Payment\DTOs\RefundPayment $refund): App\Payment\DTOs\RefundResult
        {
            return new App\Payment\DTOs\RefundResult(
                status: App\Enums\RefundStatus::Failed,
                amount: $refund->amount,
                failureReason: 'Gateway failed',
            );
        }

        public function verifyWebhook(Illuminate\Http\Request $request): bool
        {
            return true;
        }

        public function parseWebhook(Illuminate\Http\Request $request): App\Payment\DTOs\WebhookEvent
        {
            return new App\Payment\DTOs\WebhookEvent(type: 'noop');
        }

        public function testConnection(): bool
        {
            return true;
        }

        public function supportsRefunds(): bool
        {
            return true;
        }

        public function isManual(): bool
        {
            return false;
        }
    };

    PaymentManager::fake($failingDriver);

    $gateway = PaymentGateway::factory()->stripe()->create(['is_active' => true]);
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '80.0000',
        'paid_total' => '100.0000',
        'net_paid_total' => '100.0000',
        'refund_total' => '0.0000',
        'credit_due_total' => '20.0000',
        'payment_gateway_id' => $gateway->id,
        'currency_code' => 'USD',
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'pi_original_456',
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.orders.refund-credit.store', $order))
        ->assertRedirect();

    $refundTransaction = OrderTransaction::query()
        ->where('order_id', $order->id)
        ->where('type', TransactionType::Refund)
        ->first();

    expect($refundTransaction)->not->toBeNull()
        ->and($refundTransaction->status)->toBe(TransactionStatus::Failed);
});
