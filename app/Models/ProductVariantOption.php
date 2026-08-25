<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductVariantOptionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read string $product_variant_id
 * @property-read ProductVariant $variant
 * @property-read string $product_option_id
 * @property-read ProductOption $option
 * @property-read string $product_option_value_id
 * @property-read ProductOptionValue $value
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[UseFactory(ProductVariantOptionFactory::class)]
final class ProductVariantOption extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantOptionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<ProductOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    /**
     * @return BelongsTo<ProductOptionValue, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(ProductOptionValue::class, 'product_option_value_id');
    }
}
