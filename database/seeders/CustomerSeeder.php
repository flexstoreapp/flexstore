<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Country;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Seeder;

final class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(10)
            ->customer()
            ->create()
            ->each(function (User $customer): void {
                $addressCount = fake()->numberBetween(1, 3);

                CustomerAddress::factory()
                    ->forUser($customer)
                    ->default()
                    ->create([
                        'country_code' => Country::US->name,
                    ]);

                if ($addressCount > 1) {
                    CustomerAddress::factory($addressCount - 1)
                        ->forUser($customer)
                        ->create();
                }
            });
    }
}
