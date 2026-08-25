<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ResolvesStateName;
use Carbon\CarbonInterface;
use Database\Factories\CustomerAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string $address_line_1
 * @property-read string|null $address_line_2
 * @property-read string $city
 * @property-read string|null $state
 * @property-read string|null $state_name
 * @property-read string|null $postal_code
 * @property-read string $country_code
 * @property-read string|null $phone
 * @property-read bool $is_default
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 */
#[Appends(['state_name'])]
#[UseFactory(CustomerAddressFactory::class)]
final class CustomerAddress extends Model
{
    /**
     * @use HasFactory<CustomerAddressFactory>
     */
    use HasFactory;

    use ResolvesStateName;

    #[Override]
    public function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
