<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderTaxDetailItemType;
use Database\Factories\OrderTaxDetailFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read int $id
 * @property-read int $order_id
 * @property-read int|null $order_item_id
 * @property-read int|null $tax_rate_id
 * @property-read OrderTaxDetailItemType $item_type
 * @property-read string $tax_name
 * @property-read string $tax_rate
 * @property-read string $taxable_amount
 * @property-read string $tax_amount
 * @property-read string|null $proportion
 * @property-read bool $is_compound
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Translatable('tax_name')]
#[UseFactory(OrderTaxDetailFactory::class)]
final class OrderTaxDetail extends Model
{
    /** @use HasFactory<\Database\Factories\OrderTaxDetailFactory> */
    use HasFactory;

    use HasTranslations;

    #[Override]
    public function casts(): array
    {
        return [
            'item_type' => OrderTaxDetailItemType::class,
            'tax_rate' => 'decimal:2',
            'taxable_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'proportion' => 'decimal:4',
            'is_compound' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}
