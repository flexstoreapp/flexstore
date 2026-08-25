<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderAddressType;
use App\Models\Concerns\ResolvesStateName;
use Database\Factories\OrderAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $order_id
 * @property-read OrderAddressType $type
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string $address_1
 * @property-read string|null $address_2
 * @property-read string $city
 * @property-read string|null $state
 * @property-read string|null $state_name
 * @property-read string|null $postal_code
 * @property-read string $country_code
 * @property-read string|null $phone
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read Order $order
 */
#[Appends(['state_name'])]
#[UseFactory(OrderAddressFactory::class)]
final class OrderAddress extends Model
{
    /** @use HasFactory<\Database\Factories\OrderAddressFactory> */
    use HasFactory;

    use ResolvesStateName;

    #[Override]
    public function casts(): array
    {
        return [
            'type' => OrderAddressType::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
