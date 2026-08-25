<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementReason;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * @use HasFactory<StockMovementFactory>
 *
 * @property-read int $id
 * @property-read int $product_id
 * @property-read string|null $product_variant_id
 * @property-read int|null $user_id
 * @property-read int $quantity
 * @property-read int $quantity_before
 * @property-read int $quantity_after
 * @property-read StockMovementReason $reason
 * @property-read string|null $reference_type
 * @property-read string|null $reference_id
 * @property-read string|null $notes
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read Product $product
 * @property-read ProductVariant|null $productVariant
 * @property-read User|null $user
 * @property-read Model|null $reference
 */
#[UseFactory(StockMovementFactory::class)]
final class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;

    #[Override]
    public function casts(): array
    {
        return [
            'reason' => StockMovementReason::class,
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
     * @return BelongsTo<ProductVariant, $this>
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
