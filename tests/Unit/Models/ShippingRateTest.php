<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(ShippingRate::class);

uses()->group('models', 'shipping');

test('has factory', function () {
    expect(ShippingRate::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $shippingRate = ShippingRate::factory()->create([
        'rate' => '15.9900',
        'min_weight' => '0.50',
        'max_weight' => '10.00',
        'min_order_value' => '25.0000',
        'max_order_value' => '500.0000',
    ]);

    $casts = $shippingRate->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('type', ShippingRateType::class)
        ->toHaveKey('rate', 'decimal:4')
        ->toHaveKey('min_weight', 'decimal:2')
        ->toHaveKey('max_weight', 'decimal:2')
        ->toHaveKey('min_order_value', 'decimal:4')
        ->toHaveKey('max_order_value', 'decimal:4')
        ->toHaveKey('min_weight_unit', WeightUnit::class)
        ->toHaveKey('max_weight_unit', WeightUnit::class)
        ->toHaveKey('excluded_products', 'array')
        ->toHaveKey('excluded_categories', 'array')
        ->toHaveKey('excluded_brands', 'array')
        ->toHaveKey('is_active', 'boolean');
});

test('can be created via factory', function () {
    $shippingRate = ShippingRate::factory()->create();

    expect($shippingRate)
        ->toBeInstanceOf(ShippingRate::class)
        ->and($shippingRate->id)->not->toBeNull();
});

test('has region relationship', function () {
    $region = Region::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
    ]);

    expect($shippingRate->region)
        ->toBeInstanceOf(Region::class)
        ->and($shippingRate->region->id)->toBe($region->id);
});

test('has carrier relationship', function () {
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $carrier->id,
    ]);

    expect($shippingRate->carrier)
        ->toBeInstanceOf(ShippingCarrier::class)
        ->and($shippingRate->carrier->id)->toBe($carrier->id);
});
