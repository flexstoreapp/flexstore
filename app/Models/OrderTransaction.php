<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\OrderTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $order_id
 * @property-read int|null $order_refund_id
 * @property-read TransactionType $type
 * @property-read TransactionStatus $status
 * @property-read string $amount
 * @property-read string $currency_code
 * @property-read int|null $payment_gateway_id
 * @property-read string|null $gateway_reference
 * @property-read string|null $payment_method
 * @property-read array<string, mixed>|null $payment_method_details
 * @property-read string|null $failure_reason
 * @property-read string|null $payment_session_id
 * @property-read int|null $related_transaction_id
 * @property-read bool $is_manual_entry
 * @property-read array<string, mixed>|null $metadata
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 */
#[UseFactory(OrderTransactionFactory::class)]
final class OrderTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\OrderTransactionFactory> */
    use HasFactory;

    #[Override]
    public function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'amount' => 'decimal:4',
            'payment_method_details' => 'array',
            'is_manual_entry' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    /**
     * @return BelongsTo<PaymentSession, $this>
     */
    public function paymentSession(): BelongsTo
    {
        return $this->belongsTo(PaymentSession::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_transaction_id');
    }
}
