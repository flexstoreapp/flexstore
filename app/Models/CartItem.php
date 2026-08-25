<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read string $cart_id
 * @property-read int $product_id
 * @property-read string|null $product_variant_id
 * @property-read int $quantity
 * @property-read string $unit_price
 * @property-read string|null $compare_at_price
 * @property-read string $total_price
 * @property-read string|null $variant_title
 * @property-read array<string, string>|null $variant_options
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read Cart $cart
 * @property-read Product|null $product
 * @property-read ProductVariant|null $productVariant
 */
#[Touches(['cart'])]
#[UseFactory(CartItemFactory::class)]
final class CartItem extends Model
{
    /** @use HasFactory<\Database\Factories\CartItemFactory> */
    use HasFactory;

    #[Override]
    public function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'compare_at_price' => 'decimal:4',
            'total_price' => 'decimal:4',
            'variant_options' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
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
}
