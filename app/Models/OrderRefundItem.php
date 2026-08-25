<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use Database\Factories\OrderRefundItemFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * @property-read int $id
 * @property-read int $order_refund_id
 * @property-read RefundItemType $type
 * @property-read int|null $order_item_id
 * @property-read int|null $quantity
 * @property-read string $amount
 * @property-read bool $restock
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 */
#[Touches(['refund'])]
#[UseFactory(OrderRefundItemFactory::class)]
final class OrderRefundItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderRefundItemFactory> */
    use HasFactory;

    /**
     * @return Builder<OrderRefundItem>
     */
    public static function completedTotalsByOrderItem(): Builder
    {
        return self::query()
            ->select(
                'order_refund_items.order_item_id',
                DB::raw('COALESCE(SUM(order_refund_items.amount), 0) as refund_amount'),
                DB::raw('COALESCE(SUM(order_refund_items.quantity), 0) as refund_quantity'),
            )
            ->join('order_refunds', 'order_refund_items.order_refund_id', '=', 'order_refunds.id')
            ->where('order_refunds.status', RefundStatus::Completed)
            ->groupBy('order_refund_items.order_item_id');
    }

    #[Override]
    public function casts(): array
    {
        return [
            'type' => RefundItemType::class,
            'amount' => 'decimal:4',
            'restock' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<OrderRefund, $this>
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(OrderRefund::class, 'order_refund_id');
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
