<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use Database\Factories\ShippingRateFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read int $region_id
 * @property-read Region $region
 * @property-read int $shipping_carrier_id
 * @property-read ShippingCarrier $carrier
 * @property-read string $name
 * @property-read ShippingRateType $type
 * @property-read string|null $rate
 * @property-read string|null $delivery_time
 * @property-read string|null $min_order_value
 * @property-read string|null $max_order_value
 * @property-read string|null $min_weight
 * @property-read WeightUnit|null $min_weight_unit
 * @property-read string|null $max_weight
 * @property-read WeightUnit|null $max_weight_unit
 * @property-read list<int> $excluded_products
 * @property-read list<int> $excluded_categories
 * @property-read list<int> $excluded_brands
 * @property-read bool $is_active
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Translatable('name', 'delivery_time')]
#[UseFactory(ShippingRateFactory::class)]
final class ShippingRate extends Model
{
    /** @use HasFactory<\Database\Factories\ShippingRateFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'type' => ShippingRateType::class,
            'rate' => 'decimal:4',
            'min_order_value' => 'decimal:4',
            'max_order_value' => 'decimal:4',
            'min_weight' => 'decimal:2',
            'min_weight_unit' => WeightUnit::class,
            'max_weight' => 'decimal:2',
            'max_weight_unit' => WeightUnit::class,
            'excluded_products' => 'array',
            'excluded_categories' => 'array',
            'excluded_brands' => 'array',
            'is_active' => 'boolean',
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
     * @return BelongsTo<ShippingCarrier, $this>
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }
}
