<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Country;
use App\Enums\Role;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(['email' => 'admin@flexstore.app'], [
            'name' => 'Admin User',
            'url_handle' => 'admin-user',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $user->assignRole(Role::SuperAdmin);

        CustomerAddress::factory()
            ->forUser($user)
            ->default()
            ->create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'address_line_1' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country_code' => Country::US->name,
            ]);

        Category::factory(10)->create();
        Brand::factory(10)->create();

        $this->call(ProductSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(ShippingSeeder::class);
        $this->call(TaxRateSeeder::class);
        $this->call(PaymentGatewaySeeder::class);
        $this->call(CouponSeeder::class);
        $this->call(StaffSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(OrderSeeder::class);
        $this->call(StockMovementSeeder::class);
        $this->call(ReviewSeeder::class);
        $this->call(StorefrontSeeder::class);

        file_put_contents(storage_path('installed'), now()->toDateTimeString());
    }
}
