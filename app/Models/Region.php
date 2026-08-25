<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read list<string> $countries
 * @property-read list<string> $states
 * @property-read list<string> $postal_codes
 * @property-read bool $is_active
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read Collection<int, ShippingRate> $shippingRates
 */
#[Translatable('name')]
#[UseFactory(RegionFactory::class)]
final class Region extends Model
{
    /** @use HasFactory<\Database\Factories\RegionFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'countries' => 'array',
            'states' => 'array',
            'postal_codes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ShippingRate, $this>
     */
    public function shippingRates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }
}
