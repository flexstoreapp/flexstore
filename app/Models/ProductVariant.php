<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DimensionUnit;
use App\Enums\WeightUnit;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read string $id
 * @property-read int $product_id
 * @property-read Product $product
 * @property-read string $title
 * @property-read string|null $price
 * @property-read string|null $compare_at_price
 * @property-read string|null $cost_per_item
 * @property-read string|null $sku
 * @property-read string|null $barcode
 * @property-read bool $track_stock
 * @property-read int|null $stock
 * @property-read int|null $low_stock_threshold
 * @property-read bool $in_stock
 * @property-read bool $is_low_stock
 * @property-read string|null $weight
 * @property-read WeightUnit|null $weight_unit
 * @property-read string|null $length
 * @property-read string|null $width
 * @property-read string|null $height
 * @property-read DimensionUnit|null $dimension_unit
 * @property-read int|null $media_id
 * @property-read Media|null $media
 * @property-read bool $is_default
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariantOption> $options
 */
#[Touches(['product'])]
#[Translatable('title')]
#[UseFactory(ProductVariantFactory::class)]
final class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    use HasTranslations;
    use HasUuids;

    #[Override]
    public function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'compare_at_price' => 'decimal:4',
            'cost_per_item' => 'decimal:4',
            'track_stock' => 'boolean',
            'in_stock' => 'boolean',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'is_default' => 'boolean',
            'weight_unit' => WeightUnit::class,
            'dimension_unit' => DimensionUnit::class,
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductVariantOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductVariantOption::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isLowStock(): Attribute
    {
        return Attribute::get(function (): bool {
            if (! $this->track_stock || is_null($this->stock)) {
                return false;
            }

            $threshold = $this->low_stock_threshold
                ?? $this->product->low_stock_threshold
                ?? Setting::getValue('default_low_stock_threshold', 10);

            return $this->stock <= $threshold;
        });
    }
}
