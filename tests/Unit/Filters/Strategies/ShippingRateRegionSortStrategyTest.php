<?php

declare(strict_types=1);

use App\Filters\Strategies\ShippingRateRegionSortStrategy;
use App\Models\Region;
use App\Models\ShippingRate;

covers(ShippingRateRegionSortStrategy::class);

uses()->group('filters', 'shipping-rates');

test('sorts shipping rates by region name', function (): void {
    $regionA = Region::factory()->create(['name' => 'Alpha']);
    $regionB = Region::factory()->create(['name' => 'Beta']);

    $shippingRateB = ShippingRate::factory()->create(['region_id' => $regionB->id, 'name' => ['en' => 'B']]);
    $shippingRateA = ShippingRate::factory()->create(['region_id' => $regionA->id, 'name' => ['en' => 'A']]);

    $strategy = new ShippingRateRegionSortStrategy();

    $ordered = $strategy->apply(ShippingRate::query(), 'asc')->get();

    expect($ordered->pluck('id')->toArray())->toBe([$shippingRateA->id, $shippingRateB->id]);
});
