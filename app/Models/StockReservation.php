<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StockReservationFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property-read int $id
 * @property-read string $checkout_session_id
 * @property-read int $product_id
 * @property-read string|null $product_variant_id
 * @property-read int $quantity
 * @property-read \Carbon\Carbon $expires_at
 * @property-read \Carbon\Carbon|null $created_at
 */
#[UseFactory(StockReservationFactory::class)]
final class StockReservation extends Model
{
    /** @use HasFactory<\Database\Factories\StockReservationFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    #[Override]
    public function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
