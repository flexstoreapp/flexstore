<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderShipmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $order_shipment_id
 * @property-read int $order_item_id
 * @property-read int $quantity
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read OrderShipment $orderShipment
 * @property-read OrderItem $orderItem
 */
#[Touches(['orderShipment'])]
#[UseFactory(OrderShipmentItemFactory::class)]
final class OrderShipmentItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderShipmentItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<OrderShipment, $this>
     */
    public function orderShipment(): BelongsTo
    {
        return $this->belongsTo(OrderShipment::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
