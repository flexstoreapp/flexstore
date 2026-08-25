<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;

final readonly class BulkDestroyCustomerAction
{
    /**
     * @param  array<int>  $customerIds
     */
    public function handle(array $customerIds): int
    {
        return User::query()
            ->whereIn('id', $customerIds)
            ->whereRelation('roles', 'name', Role::Customer)
            ->delete();
    }
}
