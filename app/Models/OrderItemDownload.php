<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\OrderItemDownloadFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Override;

/**
 * @property-read string $id
 * @property-read int $order_id
 * @property-read int $order_item_id
 * @property-read string|null $product_download_id
 * @property-read int|null $media_id
 * @property-read int|null $customer_id
 * @property-read string $token
 * @property-read string $name
 * @property-read string $file_path
 * @property-read string $original_filename
 * @property-read int $file_size
 * @property-read string|null $mime_type
 * @property-read int $download_count
 * @property-read Carbon|null $last_downloaded_at
 * @property-read Carbon $created_at
 * @property-read bool $is_available
 * @property-read Order $order
 */
#[UseFactory(OrderItemDownloadFactory::class)]
final class OrderItemDownload extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemDownloadFactory> */
    use HasFactory;

    use HasUuids;

    public const UPDATED_AT = null;

    #[Override]
    public function casts(): array
    {
        return [
            'file_size' => 'integer',
            'download_count' => 'integer',
            'last_downloaded_at' => 'datetime',
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
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function isRevoked(): bool
    {
        return $this->order->canceled_at !== null
            || $this->order->payment_status === PaymentStatus::Refunded
            || $this->order->payment_status === PaymentStatus::Canceled;
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function isAvailable(): Attribute
    {
        return Attribute::get(fn (): bool => ! $this->isRevoked());
    }
}
