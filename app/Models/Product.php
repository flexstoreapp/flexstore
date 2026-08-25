<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasMedia;
use App\Enums\DimensionUnit;
use App\Enums\ProductType;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read int|null $category_id
 * @property-read Category|null $category
 * @property-read TaxCategory|null $tax_category
 * @property-read int|null $brand_id
 * @property-read Brand|null $brand
 * @property-read string $url_handle
 * @property-read string $title
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Media> $mediaGallery
 * @property-read Media|null $featured_media
 * @property-read string $description
 * @property-read string|null $price
 * @property-read array<string>|null $price_range
 * @property-read string|null $compare_at_price
 * @property-read string|null $cost_per_item
 * @property-read string|null $sku
 * @property-read string|null $barcode
 * @property-read bool $track_stock
 * @property-read int|null $stock
 * @property-read int|null $low_stock_threshold
 * @property-read int $total_stock
 * @property-read bool|null $in_stock
 * @property-read bool|null $is_low_stock
 * @property-read string|null $weight
 * @property-read WeightUnit|null $weight_unit
 * @property-read string|null $length
 * @property-read string|null $width
 * @property-read string|null $height
 * @property-read DimensionUnit|null $dimension_unit
 * @property-read bool $is_tax_exempt
 * @property-read bool $is_active
 * @property-read string $seo_title
 * @property-read string $seo_description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Review> $reviews
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read float|null $reviews_avg_rating
 * @property-read int|null $total_sold
 * @property-read string|null $revenue
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOption> $options
 * @property-read ProductType $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductDownload> $downloads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariant> $variants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariantOption> $variantOptions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StockMovement> $stockMovements
 */
#[Translatable('title', 'description', 'seo_title', 'seo_description')]
#[UseFactory(ProductFactory::class)]
final class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    use HasMedia;
    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price' => 'decimal:4',
            'compare_at_price' => 'decimal:4',
            'cost_per_item' => 'decimal:4',
            'track_stock' => 'boolean',
            'in_stock' => 'boolean',
            'weight' => 'decimal:2',
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'is_tax_exempt' => 'boolean',
            'is_active' => 'boolean',
            'weight_unit' => WeightUnit::class,
            'dimension_unit' => DimensionUnit::class,
            'tax_category' => TaxCategory::class,
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductDownload, $this>
     */
    public function downloads(): HasMany
    {
        return $this->hasMany(ProductDownload::class)->orderBy('sort_order');
    }

    public function isDigital(): bool
    {
        return $this->type === ProductType::Digital;
    }

    public function requiresShipping(): bool
    {
        return $this->type->requiresShipping();
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->whereNull('product_variant_id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return MorphToMany<Media, $this>
     */
    public function mediaGallery(): MorphToMany
    {
        return $this->morphMedia();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function withFeaturedMedia(Builder $query): Builder
    {
        return $query->with([
            'mediaGallery' => fn (Relation $relation): Relation => $relation
                ->select(Media::displayColumns())
                ->limit(1),
        ]);
    }

    /**
     * @return Attribute<Media|null, never>
     */
    protected function featuredMedia(): Attribute
    {
        return Attribute::get(fn (): ?Media => $this->mediaGallery->first());
    }

    /**
     * @return Attribute<array{string, string}|null, never>
     */
    protected function priceRange(): Attribute
    {
        return Attribute::get(function (): ?array {
            if (! is_null($this->price) && $this->variants->isEmpty()) {
                return [$this->price, $this->price];
            }

            if ($this->variants->isNotEmpty()) {
                $variantPrices = $this->variants->pluck('price')->filter()->values();

                if ($variantPrices->isEmpty()) {
                    return null;
                }

                return [(string) $variantPrices->min(), (string) $variantPrices->max()];
            }

            return null;
        });
    }

    /**
     * @return Attribute<array{string, string}|null, never>
     */
    protected function compareAtPriceRange(): Attribute
    {
        return Attribute::get(function (): ?array {
            if (! is_null($this->compare_at_price) && $this->variants->isEmpty()) {
                return [$this->compare_at_price, $this->compare_at_price];
            }

            if ($this->variants->isNotEmpty()) {
                $variantPrices = $this->variants->pluck('compare_at_price')->filter()->values();

                if ($variantPrices->count() !== $this->variants->count()) {
                    return null;
                }

                return [(string) $variantPrices->min(), (string) $variantPrices->max()];
            }

            return null;
        });
    }

    /**
     * @return Attribute<int, never>
     */
    protected function totalStock(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->variants->isNotEmpty()) {
                return $this->variants->sum('stock');
            }

            return (int) ($this->stock ?? 0);
        });
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

            $threshold = $this->low_stock_threshold ?? Setting::getValue('default_low_stock_threshold', 10);

            return $this->stock <= $threshold;
        });
    }
}
