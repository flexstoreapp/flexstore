<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class CustomerAddressListQuery
{
    /**
     * @return Collection<int, CustomerAddress>
     */
    public function execute(User $user): Collection
    {
        return $user->addresses()
            ->select([
                'id', 'user_id', 'is_default',
                'first_name', 'last_name',
                'address_line_1', 'address_line_2',
                'city', 'state', 'postal_code', 'country_code', 'phone',
            ])
            ->orderByDesc('is_default')
            ->latest()
            ->get();
    }
}
