<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Country;
use App\Enums\ShippingCarrierDriver;
use App\Enums\ShippingRateType;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use Illuminate\Database\Seeder;

final class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        $this->createShippingCarriers();
        $this->createShippingRates();
    }

    private function createShippingCarriers(): void
    {
        ShippingCarrier::query()->create([
            'name' => ['en' => 'Standard Shipping', 'ar' => 'الشحن العادي'],
            'driver' => ShippingCarrierDriver::Manual,
            'is_active' => true,
        ]);

        ShippingCarrier::query()->create([
            'name' => ['en' => 'Express Delivery', 'ar' => 'التوصيل السريع'],
            'driver' => ShippingCarrierDriver::Manual,
            'is_active' => true,
        ]);
    }

    private function createShippingRates(): void
    {
        $carriers = ShippingCarrier::query()->orderBy('id')->get();
        $standard = $carriers->first();
        $express = $carriers->last();
        $northAmerica = $this->northAmericaRegion();
        $europe = $this->europeRegion();

        ShippingRate::query()->create([
            'region_id' => $northAmerica->id,
            'shipping_carrier_id' => $standard->id,
            'name' => ['en' => 'Free Standard Shipping', 'ar' => 'شحن عادي مجاني'],
            'type' => ShippingRateType::Free,
            'rate' => 0,
            'delivery_time' => ['en' => '5-7 business days', 'ar' => '5-7 أيام عمل'],
            'min_order_value' => 50,
            'is_active' => true,
        ]);

        ShippingRate::query()->create([
            'region_id' => $northAmerica->id,
            'shipping_carrier_id' => $standard->id,
            'name' => ['en' => 'Standard Shipping', 'ar' => 'الشحن العادي'],
            'type' => ShippingRateType::Flat,
            'rate' => 5.99,
            'delivery_time' => ['en' => '5-7 business days', 'ar' => '5-7 أيام عمل'],
            'max_order_value' => 49.99,
            'is_active' => true,
        ]);

        ShippingRate::query()->create([
            'region_id' => $northAmerica->id,
            'shipping_carrier_id' => $express->id,
            'name' => ['en' => 'Express Shipping', 'ar' => 'الشحن السريع'],
            'type' => ShippingRateType::Flat,
            'rate' => 14.99,
            'delivery_time' => ['en' => '1-2 business days', 'ar' => '1-2 أيام عمل'],
            'is_active' => true,
        ]);

        ShippingRate::query()->create([
            'region_id' => $europe->id,
            'shipping_carrier_id' => $standard->id,
            'name' => ['en' => 'International Standard', 'ar' => 'الشحن الدولي العادي'],
            'type' => ShippingRateType::Flat,
            'rate' => 19.99,
            'delivery_time' => ['en' => '7-14 business days', 'ar' => '7-14 أيام عمل'],
            'is_active' => true,
        ]);
    }

    private function northAmericaRegion(): Region
    {
        return $this->region(
            [Country::US->name, Country::CA->name, Country::MX->name],
            ['en' => 'North America', 'ar' => 'أمريكا الشمالية'],
        );
    }

    private function europeRegion(): Region
    {
        return $this->region(
            [
                Country::GB->name, Country::DE->name, Country::FR->name, Country::IT->name, Country::ES->name,
                Country::NL->name, Country::SE->name, Country::NO->name, Country::DK->name, Country::FI->name,
            ],
            ['en' => 'Europe', 'ar' => 'أوروبا'],
        );
    }

    /**
     * @param  list<string>  $countries
     * @param  array<string, string>  $name
     */
    private function region(array $countries, array $name): Region
    {
        $existing = Region::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (Region $region): bool => in_array($countries[0], $region->countries ?? [], true));

        return $existing ?? Region::query()->create([
            'name' => $name,
            'countries' => $countries,
            'is_active' => true,
        ]);
    }
}
