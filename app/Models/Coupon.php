<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponType;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property-read int $id
 * @property-read string $code
 * @property-read CouponType $type
 * @property-read string $value
 * @property-read string|null $min_order_value
 * @property-read string|null $maximum_discount
 * @property-read int|null $usage_limit
 * @property-read int|null $usage_limit_per_customer
 * @property-read int $used_count
 * @property-read bool $is_active
 * @property-read \Carbon\Carbon|null $starts_at
 * @property-read \Carbon\Carbon|null $expires_at
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 */
#[UseFactory(CouponFactory::class)]
final class Coupon extends Model
{
    /** @use HasFactory<\Database\Factories\CouponFactory> */
    use HasFactory;

    public static function normalizeCode(string $code): string
    {
        return mb_strtoupper(mb_trim($code));
    }

    #[Override]
    public function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'decimal:4',
            'min_order_value' => 'decimal:4',
            'maximum_discount' => 'decimal:4',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return Attribute<never, string>
     */
    protected function code(): Attribute
    {
        return Attribute::set(fn (string $value): string => self::normalizeCode($value));
    }
}
