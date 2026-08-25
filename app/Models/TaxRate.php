<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaxCategory;
use Database\Factories\TaxRateFactory;
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
 * @property-read string $name
 * @property-read int $region_id
 * @property-read TaxCategory|null $tax_category
 * @property-read Region $region
 * @property-read string $rate
 * @property-read string|null $min_order_value
 * @property-read string|null $max_order_value
 * @property-read bool $is_compound
 * @property-read bool $is_active
 * @property-read int $priority
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Translatable('name')]
#[UseFactory(TaxRateFactory::class)]
final class TaxRate extends Model
{
    /** @use HasFactory<\Database\Factories\TaxRateFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'min_order_value' => 'decimal:4',
            'max_order_value' => 'decimal:4',
            'is_compound' => 'boolean',
            'is_active' => 'boolean',
            'tax_category' => TaxCategory::class,
        ];
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * @return HasMany<OrderTaxDetail, $this>
     */
    public function orderTaxDetails(): HasMany
    {
        return $this->hasMany(OrderTaxDetail::class);
    }
}
