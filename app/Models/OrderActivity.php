<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderActivityType;
use Carbon\CarbonInterface;
use Database\Factories\OrderActivityFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $order_id
 * @property-read int|null $user_id
 * @property-read OrderActivityType $type
 * @property-read string|null $comment
 * @property-read array<string, mixed>|null $metadata
 * @property-read CarbonInterface $created_at
 * @property-read Order $order
 * @property-read User|null $user
 */
#[UseFactory(OrderActivityFactory::class)]
final class OrderActivity extends Model
{
    /** @use HasFactory<\Database\Factories\OrderActivityFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    #[Override]
    public function casts(): array
    {
        return [
            'type' => OrderActivityType::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
