<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductDownloadFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read string $id
 * @property-read int $product_id
 * @property-read string|null $product_variant_id
 * @property-read int $media_id
 * @property-read string $name
 * @property-read int $sort_order
 * @property-read Media $media
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[UseFactory(ProductDownloadFactory::class)]
final class ProductDownload extends Model
{
    /** @use HasFactory<\Database\Factories\ProductDownloadFactory> */
    use HasFactory;

    use HasUuids;

    #[Override]
    public function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
