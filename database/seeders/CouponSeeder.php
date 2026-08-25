<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

final class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::factory()->valid()->create([
            'code' => 'WELCOME10',
            'type' => CouponType::Percentage,
            'value' => 10,
            'min_order_value' => 50.00,
            'maximum_discount' => 20.00,
            'usage_limit' => 100,
            'usage_limit_per_customer' => 1,
            'is_active' => true,
        ]);

        Coupon::factory()->valid()->create([
            'code' => 'SAVE20',
            'type' => CouponType::Flat,
            'value' => 20.00,
            'min_order_value' => 100.00,
            'usage_limit' => 50,
            'usage_limit_per_customer' => 2,
            'is_active' => true,
        ]);

        Coupon::factory()->valid()->create([
            'code' => 'FLASH25',
            'type' => CouponType::Percentage,
            'value' => 25,
            'maximum_discount' => 50.00,
            'usage_limit' => 200,
            'is_active' => true,
            'expires_at' => now()->addDays(7),
        ]);

        Coupon::factory()->valid()->create([
            'code' => 'EXPIRED',
            'type' => CouponType::Percentage,
            'value' => 15,
            'is_active' => true,
            'expires_at' => now()->subDays(1),
        ]);

        Coupon::factory()->valid()->create([
            'code' => 'INACTIVE',
            'type' => CouponType::Flat,
            'value' => 10.00,
            'is_active' => false,
        ]);
    }
}
