<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateCustomerInput;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCustomerAction
{
    public function handle(User $customer, UpdateCustomerInput $input): User
    {
        return DB::transaction(function () use ($customer, $input): User {
            $updateData = [];

            if ($input->has('name')) {
                $updateData['name'] = $input->name;
            }

            if ($input->has('email')) {
                $updateData['email'] = $input->email;
            }

            if ($input->password !== null) {
                $updateData['password'] = $input->password;
            }

            if ($updateData !== []) {
                $customer->update($updateData);
            }

            return $customer;
        });
    }
}
