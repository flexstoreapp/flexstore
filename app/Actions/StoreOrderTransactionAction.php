<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\OrderRefund;
use App\Models\OrderTransaction;

final readonly class StoreOrderTransactionAction
{
    /**
     * @param  array<string, mixed>|null  $paymentMethodDetails
     * @param  array<string, mixed>|null  $metadata
     */
    public function handle(
        Order $order,
        TransactionType $type,
        TransactionStatus $status,
        string $amount,
        ?OrderRefund $refund = null,
        ?string $gatewayReference = null,
        ?string $paymentMethod = null,
        ?array $paymentMethodDetails = null,
        ?string $failureReason = null,
        bool $isManualEntry = false,
        ?int $relatedTransactionId = null,
        ?int $paymentGatewayId = null,
        ?array $metadata = null,
    ): OrderTransaction {
        return OrderTransaction::query()->create([
            'order_id' => $order->id,
            'order_refund_id' => $refund?->id,
            'type' => $type,
            'status' => $status,
            'amount' => $amount,
            'currency_code' => $order->currency_code,
            'payment_gateway_id' => $paymentGatewayId ?? $order->paymentGateway?->id,
            'gateway_reference' => $gatewayReference,
            'payment_method' => $paymentMethod,
            'payment_method_details' => $paymentMethodDetails,
            'failure_reason' => $failureReason,
            'is_manual_entry' => $isManualEntry,
            'related_transaction_id' => $relatedTransactionId,
            'metadata' => $metadata,
        ]);
    }
}
