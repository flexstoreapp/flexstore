<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read int $order_id
 * @property-read int|null $product_id
 * @property-read string|null $product_variant_id
 * @property-read array<string, string> $product_title
 * @property-read string|null $product_sku
 * @property-read string|null $variant_title
 * @property-read array<string, string>|null $variant_options
 * @property-read int $quantity
 * @property-read string $unit_price
 * @property-read string $total_price
 * @property-read string|null $cost_per_item Unit cost at sale time, in base currency
 * @property-read string $tax_amount
 * @property-read bool $requires_shipping
 * @property-read string|null $weight
 * @property-read string|null $weight_unit
 * @property-read string|null $length
 * @property-read string|null $width
 * @property-read string|null $height
 * @property-read string|null $dimension_unit
 * @property-read int|null $media_id
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read Order $order
 * @property-read Product|null $product
 * @property-read ProductVariant|null $productVariant
 * @property-read Media|null $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderTaxDetail> $taxDetails
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderRefundItem> $refundItems
 */
#[Touches(['order'])]
#[Translatable('product_title')]
#[UseFactory(OrderItemFactory::class)]
final class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'total_price' => 'decimal:4',
            'cost_per_item' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'weight' => 'decimal:2',
            'requires_shipping' => 'boolean',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'variant_options' => 'array',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * @return HasMany<OrderRefundItem, $this>
     */
    public function refundItems(): HasMany
    {
        return $this->hasMany(OrderRefundItem::class);
    }

    /**
     * @return HasMany<OrderTaxDetail, $this>
     */
    public function taxDetails(): HasMany
    {
        return $this->hasMany(OrderTaxDetail::class);
    }
}
