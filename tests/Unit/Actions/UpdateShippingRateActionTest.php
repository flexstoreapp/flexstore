<?php

declare(strict_types=1);

use App\Actions\UpdateShippingRateAction;
use App\DTOs\UpdateShippingRateInput;
use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;

covers(UpdateShippingRateAction::class, UpdateShippingRateInput::class);

uses()->group('actions', 'shipping');

test('updates a flat rate shipping', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Old Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '5.9900',
    ]);

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Updated Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'min_order_value' => '100',
        'max_order_value' => '500.0000',
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '5.00',
        'max_weight_unit' => WeightUnit::Kg->value,
        'is_active' => true,
    ];

    $action = new UpdateShippingRateAction();
    $updatedRate = $action->handle($shippingRate->fresh(), UpdateShippingRateInput::fromArray($data));

    expect($updatedRate->id)->toBe($shippingRate->id)
        ->and($updatedRate->region_id)->toBe($region->id)
        ->and($updatedRate->shipping_carrier_id)->toBe($carrier->id)
        ->and($updatedRate->name)->toBe('Updated Rate')
        ->and($updatedRate->type)->toBe(ShippingRateType::Flat)
        ->and($updatedRate->rate)->toBe('10.9900')
        ->and($updatedRate->min_order_value)->toBe('100.0000')
        ->and($updatedRate->max_order_value)->toBe('500.0000')
        ->and($updatedRate->min_weight)->toBe('1.00')
        ->and($updatedRate->min_weight_unit)->toBe(WeightUnit::Kg)
        ->and($updatedRate->max_weight)->toBe('5.00')
        ->and($updatedRate->max_weight_unit)->toBe(WeightUnit::Kg)
        ->and($updatedRate->is_active)->toBeTrue();
});

test('updates a rate type', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Flat Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '5.9900',
    ]);

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Weight-Based Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'min_order_value' => null,
        'max_order_value' => null,
        'min_weight' => '0.00',
        'max_weight' => '5.00',
        'is_active' => true,
    ];

    $action = new UpdateShippingRateAction();
    $updatedRate = $action->handle($shippingRate->fresh(), UpdateShippingRateInput::fromArray($data));

    expect($updatedRate->id)->toBe($shippingRate->id)
        ->and($updatedRate->name)->toBe('Weight-Based Rate')
        ->and($updatedRate->type)->toBe(ShippingRateType::Flat)
        ->and($updatedRate->rate)->toBe('10.9900')
        ->and($updatedRate->min_weight)->toBe('0.00')
        ->and($updatedRate->max_weight)->toBe('5.00');
});

test('updates to a free shipping rate', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Paid Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '9.9900',
    ]);

    $data = [
        'name' => 'Free Shipping',
        'type' => ShippingRateType::Free->value,
        'rate' => null,
    ];

    $action = new UpdateShippingRateAction();
    $updatedRate = $action->handle($shippingRate->fresh(), UpdateShippingRateInput::fromArray($data));

    expect($updatedRate->id)->toBe($shippingRate->id)
        ->and($updatedRate->name)->toBe('Free Shipping')
        ->and($updatedRate->type)->toBe(ShippingRateType::Free)
        ->and($updatedRate->rate)->toBeNull();
});

test('marks a shipping rate inactive', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'is_active' => true,
    ]);

    $data = [
        'region_id' => $shippingRate->region_id,
        'shipping_carrier_id' => $shippingRate->shipping_carrier_id,
        'name' => $shippingRate->name,
        'type' => $shippingRate->type,
        'rate' => $shippingRate->rate,
        'min_order_value' => $shippingRate->min_order_value,
        'max_order_value' => $shippingRate->max_order_value,
        'min_weight' => $shippingRate->min_weight,
        'max_weight' => $shippingRate->max_weight,
        'is_active' => false,
    ];

    $action = new UpdateShippingRateAction();
    $updatedRate = $action->handle($shippingRate->fresh(), UpdateShippingRateInput::fromArray($data));

    expect($updatedRate->id)->toBe($shippingRate->id)
        ->and($updatedRate->is_active)->toBeFalse();
});

test('uses provided rate value directly', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'type' => ShippingRateType::Free,
        'rate' => null,
    ]);

    $data = [
        'name' => 'Updated Free Rate',
        'type' => ShippingRateType::Free->value,
        'rate' => null,
    ];

    $action = new UpdateShippingRateAction();
    $updatedRate = $action->handle($shippingRate->fresh(), UpdateShippingRateInput::fromArray($data));

    expect($updatedRate->rate)->toBeNull();
});

test('falls back to existing rate when rate is not provided', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'type' => ShippingRateType::Flat,
        'rate' => '5.9900',
    ]);

    $data = [
        'name' => 'Updated Flat Rate',
    ];

    $action = new UpdateShippingRateAction();
    $updatedRate = $action->handle($shippingRate->fresh(), UpdateShippingRateInput::fromArray($data));

    expect($updatedRate->rate)->toBe('5.9900');
});

test('updates weight-based shipping rate with weight units', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Old Weight Rate',
        'type' => ShippingRateType::Flat->value,
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '5.00',
        'max_weight_unit' => WeightUnit::Kg->value,
    ]);

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Updated Weight Rate',
        'type' => ShippingRateType::Flat->value,
        'min_weight' => '2.00',
        'min_weight_unit' => WeightUnit::Lb->value,
        'max_weight' => '10.00',
        'max_weight_unit' => WeightUnit::Lb->value,
        'is_active' => true,
    ];

    $action = new UpdateShippingRateAction();
    $updatedRate = $action->handle($shippingRate->fresh(), UpdateShippingRateInput::fromArray($data));

    expect($updatedRate->id)->toBe($shippingRate->id)
        ->and($updatedRate->name)->toBe('Updated Weight Rate')
        ->and($updatedRate->type)->toBe(ShippingRateType::Flat)
        ->and($updatedRate->min_weight)->toBe('2.00')
        ->and($updatedRate->min_weight_unit->value)->toBe(WeightUnit::Lb->value)
        ->and($updatedRate->max_weight)->toBe('10.00')
        ->and($updatedRate->max_weight_unit->value)->toBe(WeightUnit::Lb->value);
});
