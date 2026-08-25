<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ShippingCarrierDriver;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(ShippingCarrier::class);

uses()->group('models', 'shipping');

test('has factory', function () {
    expect(ShippingCarrier::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $carrier = new ShippingCarrier();
    $casts = $carrier->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('driver', ShippingCarrierDriver::class)
        ->toHaveKey('excluded_products', 'array')
        ->toHaveKey('excluded_categories', 'array')
        ->toHaveKey('is_active', 'boolean');
});

test('can be created via factory', function () {
    $carrier = ShippingCarrier::factory()->create();

    expect($carrier)
        ->toBeInstanceOf(ShippingCarrier::class)
        ->and($carrier->id)->not->toBeNull();
});

test('has rates relationship', function () {
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $carrier->id,
    ]);

    expect($carrier->rates)
        ->toBeInstanceOf(Collection::class)
        ->and($carrier->rates->first())->toBeInstanceOf(ShippingRate::class)
        ->and($carrier->rates->first()->id)->toBe($shippingRate->id);
});
