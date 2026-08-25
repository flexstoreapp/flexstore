<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;
use Database\Factories\PaymentGatewayFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read PaymentGatewayDriver $driver
 * @property-read array<string, string>|null $credentials
 * @property-read string|null $min_order_value
 * @property-read string|null $max_order_value
 * @property-read string|null $min_weight
 * @property-read WeightUnit|null $min_weight_unit
 * @property-read string|null $max_weight
 * @property-read WeightUnit|null $max_weight_unit
 * @property-read list<string> $excluded_products
 * @property-read list<string> $excluded_categories
 * @property-read list<int> $excluded_brands
 * @property-read list<string> $allowed_regions
 * @property-read list<string> $supported_currencies
 * @property-read bool $sync_external_refunds
 * @property-read bool $is_active
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Hidden(['credentials'])]
#[Translatable('name')]
#[UseFactory(PaymentGatewayFactory::class)]
final class PaymentGateway extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentGatewayFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'driver' => PaymentGatewayDriver::class,
            'min_order_value' => 'decimal:4',
            'max_order_value' => 'decimal:4',
            'min_weight' => 'decimal:2',
            'min_weight_unit' => WeightUnit::class,
            'max_weight' => 'decimal:2',
            'max_weight_unit' => WeightUnit::class,
            'credentials' => 'encrypted:array',
            'excluded_products' => 'array',
            'excluded_categories' => 'array',
            'excluded_brands' => 'array',
            'allowed_regions' => 'array',
            'supported_currencies' => 'array',
            'sync_external_refunds' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
