<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read string $id
 * @property-read int $product_id
 * @property-read Product $product
 * @property-read string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOptionValue> $values
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Touches(['product'])]
#[Translatable('name')]
#[UseFactory(ProductOptionFactory::class)]
final class ProductOption extends Model
{
    /** @use HasFactory<\Database\Factories\ProductOptionFactory> */
    use HasFactory;

    use HasTranslations;
    use HasUuids;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductOptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
