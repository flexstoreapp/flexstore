<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductOptionValueFactory;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

/**
 * @property-read string $id
 * @property-read string $product_option_id
 * @property-read ProductOption $option
 * @property-read string $value
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[Touches(['option'])]
#[Translatable('value')]
#[UseFactory(ProductOptionValueFactory::class)]
final class ProductOptionValue extends Model
{
    /** @use HasFactory<\Database\Factories\ProductOptionValueFactory> */
    use HasFactory;

    use HasTranslations;
    use HasUuids;

    /**
     * @return BelongsTo<ProductOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }
}
