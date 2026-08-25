<?php

declare(strict_types=1);

use App\Filters\Strategies\ShippingRateCarrierSortStrategy;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;

covers(ShippingRateCarrierSortStrategy::class);

uses()->group('filters', 'shipping-rates');

test('sorts shipping rates by carrier name', function (): void {
    $region = Region::factory()->create();
    $carrierA = ShippingCarrier::factory()->create(['name' => ['en' => 'Alpha Carrier']]);
    $carrierB = ShippingCarrier::factory()->create(['name' => ['en' => 'Beta Carrier']]);

    $shippingRateB = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrierB->id,
        'name' => ['en' => 'B'],
    ]);
    $shippingRateA = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrierA->id,
        'name' => ['en' => 'A'],
    ]);

    $strategy = new ShippingRateCarrierSortStrategy();

    $ordered = $strategy->apply(ShippingRate::query(), 'asc')->get();

    expect($ordered->pluck('id')->toArray())->toBe([$shippingRateA->id, $shippingRateB->id]);
});
