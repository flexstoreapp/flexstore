<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentSessionPurpose;
use App\Enums\PaymentSessionStatus;
use Database\Factories\PaymentSessionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * @property-read string $id
 * @property-read int $payment_gateway_id
 * @property-read string $owner_type
 * @property-read string $owner_id
 * @property-read PaymentSessionPurpose $purpose
 * @property-read string $currency_code
 * @property-read string $amount
 * @property-read string|null $customer_email
 * @property-read string|null $description
 * @property-read string $return_url
 * @property-read string|null $cancel_url
 * @property-read string|null $failure_url
 * @property-read string|null $webhook_url
 * @property-read string|null $gateway_reference
 * @property-read array<string, mixed>|null $payload
 * @property-read array<string, mixed>|null $metadata
 * @property-read PaymentSessionStatus $status
 * @property-read \Carbon\Carbon|null $completed_at
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read PaymentGateway $paymentGateway
 */
#[UseFactory(PaymentSessionFactory::class)]
final class PaymentSession extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentSessionFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    public function casts(): array
    {
        return [
            'purpose' => PaymentSessionPurpose::class,
            'status' => PaymentSessionStatus::class,
            'amount' => 'decimal:4',
            'payload' => 'array',
            'metadata' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
