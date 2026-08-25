<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RefundStatus;
use Database\Factories\OrderRefundFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read int $id
 * @property-read int $order_id
 * @property-read RefundStatus $status
 * @property-read string $amount
 * @property-read bool $is_manual_total
 * @property-read string|null $reason
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read Order $order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderRefundItem> $items
 */
#[Touches(['order'])]
#[UseFactory(OrderRefundFactory::class)]
final class OrderRefund extends Model
{
    /** @use HasFactory<\Database\Factories\OrderRefundFactory> */
    use HasFactory;

    #[Override]
    public function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount' => 'decimal:4',
            'is_manual_total' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<OrderRefundItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderRefundItem::class);
    }
}
