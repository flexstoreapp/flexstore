<?php

declare(strict_types=1);

use App\Models\ShippingCarrier;
use App\Queries\ShippingCarrierListQuery;

covers(ShippingCarrierListQuery::class);

uses()->group('queries', 'shipping');

test('returns empty collection when no shipping carriers exist', function (): void {
    actingAsSuperAdmin();

    $query = new ShippingCarrierListQuery();
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('returns all shipping carriers', function (): void {
    actingAsSuperAdmin();

    ShippingCarrier::factory()->active()->create(['name' => 'Carrier 1']);
    ShippingCarrier::factory()->inactive()->create(['name' => 'Carrier 2']);
    ShippingCarrier::factory()->active()->create(['name' => 'Carrier 3']);

    $query = new ShippingCarrierListQuery();
    $result = $query->execute();

    expect($result)->toHaveCount(3);
});

test('maintains carrier order from database', function (): void {
    actingAsSuperAdmin();

    $carrier1 = ShippingCarrier::factory()->active()->create(['name' => 'Carrier A']);
    $carrier2 = ShippingCarrier::factory()->active()->create(['name' => 'Carrier B']);
    $carrier3 = ShippingCarrier::factory()->active()->create(['name' => 'Carrier C']);

    $query = new ShippingCarrierListQuery();
    $result = $query->execute();

    expect($result->pluck('id')->toArray())
        ->toBe([$carrier1->id, $carrier2->id, $carrier3->id]);
});
