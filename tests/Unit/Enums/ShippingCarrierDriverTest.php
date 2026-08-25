<?php

declare(strict_types=1);

use App\Enums\ShippingCarrierDriver;
use App\Models\ShippingCarrier;
use App\Shipping\Drivers\ManualDriver;

covers(ShippingCarrierDriver::class);

uses()->group('enums', 'shipping');

test('exposes every supported driver value', function () {
    expect(array_map(fn (ShippingCarrierDriver $case): string => $case->value, ShippingCarrierDriver::cases()))
        ->toBe(['manual']);
});

test('make builds the manual driver', function () {
    $carrier = ShippingCarrier::factory()->create(['driver' => ShippingCarrierDriver::Manual]);

    expect(ShippingCarrierDriver::Manual->make($carrier))->toBeInstanceOf(ManualDriver::class);
});
