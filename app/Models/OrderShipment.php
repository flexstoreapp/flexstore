<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderShipmentFactory;
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
 * @property-read int|null $user_id
 * @property-read int|null $shipping_carrier_id
 * @property-read string|null $tracking_number
 * @property-read string|null $tracking_url
 * @property-read \Carbon\Carbon|null $shipped_at
 * @property-read \Carbon\Carbon|null $delivered_at
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read Order $order
 * @property-read User|null $user
 * @property-read ShippingCarrier|null $carrier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderShipmentItem> $items
 */
#[Touches(['order'])]
#[UseFactory(OrderShipmentFactory::class)]
final class OrderShipment extends Model
{
    /** @use HasFactory<\Database\Factories\OrderShipmentFactory> */
    use HasFactory;

    #[Override]
    public function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
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
     * @return BelongsTo<ShippingCarrier, $this>
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderShipmentItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderShipmentItem::class);
    }
}
