<?php

declare(strict_types=1);

namespace App\Models;

use App\Utilities\WeightConverter;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read string $id
 * @property-read int|null $customer_id
 * @property-read int|null $shipping_rate_id
 * @property-read string|null $shipping_quote_reference
 * @property-read int|null $payment_gateway_id
 * @property-read string|null $coupon_code
 * @property-read string $subtotal
 * @property-read string $tax_total
 * @property-read string $shipping_total
 * @property-read string $discount_total
 * @property-read string $total
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CartItem> $items
 * @property-read User|null $customer
 * @property-read ShippingRate|null $shippingRate
 * @property-read PaymentGateway|null $paymentGateway
 * @property-read string $weight_in_grams
 */
#[UseFactory(CartFactory::class)]
final class Cart extends Model
{
    /** @use HasFactory<\Database\Factories\CartFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    public function casts(): array
    {
        return [
            'subtotal' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'shipping_total' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function requiresShipping(): bool
    {
        $this->loadMissing('items.product');

        return $this->items->contains(
            fn (CartItem $item): bool => $item->product?->requiresShipping() ?? false,
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<ShippingRate, $this>
     */
    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class);
    }

    /**
     * @return BelongsTo<PaymentGateway, $this>
     */
    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    /**
     * @return Attribute<numeric-string, never>
     */
    protected function weightInGrams(): Attribute
    {
        return Attribute::get(function (): string {
            $this->loadMissing('items.product', 'items.productVariant');

            $totalWeightInGrams = BigDecimal::zero();

            foreach ($this->items as $item) {
                $weight = null;
                $weightUnit = null;

                if ($item->product_variant_id !== null && $item->productVariant !== null) {
                    $weight = $item->productVariant->weight;
                    $weightUnit = $item->productVariant->weight_unit;
                } elseif ($item->product !== null) {
                    $weight = $item->product->weight;
                    $weightUnit = $item->product->weight_unit;
                }

                if ($weight !== null && $weightUnit !== null) {
                    $itemWeight = BigDecimal::of($weight)->multipliedBy($item->quantity)->toString();
                    $itemWeightInGrams = WeightConverter::toGrams($itemWeight, $weightUnit);
                    $totalWeightInGrams = $totalWeightInGrams->plus(BigDecimal::of($itemWeightInGrams));
                }
            }

            return $totalWeightInGrams->toScale(2, RoundingMode::HalfUp)->toString();
        });
    }
}
