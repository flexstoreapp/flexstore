<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ShippingCarrierDriver;
use Database\Factories\ShippingCarrierFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read ShippingCarrierDriver $driver
 * @property-read bool $is_active
 * @property-read bool $collects_cod
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ShippingRate> $rates
 */
#[Translatable('name')]
#[UseFactory(ShippingCarrierFactory::class)]
final class ShippingCarrier extends Model
{
    /** @use HasFactory<\Database\Factories\ShippingCarrierFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'driver' => ShippingCarrierDriver::class,
            'excluded_products' => 'array',
            'excluded_categories' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ShippingRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function collectsCod(): Attribute
    {
        return Attribute::get(fn (): bool => $this->driver->make($this)->collectsCod());
    }
}
