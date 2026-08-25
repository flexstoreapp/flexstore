<?php

declare(strict_types=1);

use App\Actions\StoreShippingRateAction;
use App\DTOs\StoreShippingRateInput;
use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;

covers(StoreShippingRateAction::class, StoreShippingRateInput::class);

uses()->group('actions', 'shipping');

test('creates a flat rate shipping', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Standard Shipping',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'min_order_value' => '100',
        'max_order_value' => '200',
        'min_weight' => '10.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '20.00',
        'max_weight_unit' => WeightUnit::Kg->value,
        'is_active' => true,
    ];

    $action = new StoreShippingRateAction();
    $shippingRate = $action->handle(StoreShippingRateInput::fromArray($data));

    expect($shippingRate)->toBeInstanceOf(ShippingRate::class)
        ->and($shippingRate->region_id)->toBe($region->id)
        ->and($shippingRate->shipping_carrier_id)->toBe($carrier->id)
        ->and($shippingRate->name)->toBe('Standard Shipping')
        ->and($shippingRate->type)->toBe(ShippingRateType::Flat)
        ->and($shippingRate->rate)->toBe('10.9900')
        ->and($shippingRate->min_order_value)->toBe('100.0000')
        ->and($shippingRate->max_order_value)->toBe('200.0000')
        ->and($shippingRate->min_weight)->toBe('10.00')
        ->and($shippingRate->min_weight_unit)->toBe(WeightUnit::Kg)
        ->and($shippingRate->max_weight)->toBe('20.00')
        ->and($shippingRate->max_weight_unit)->toBe(WeightUnit::Kg)
        ->and($shippingRate->is_active)->toBeTrue();
});

test('creates a free shipping rate', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Free Shipping',
        'type' => ShippingRateType::Free->value,
        'rate' => null,
        'is_active' => true,
    ];

    $action = new StoreShippingRateAction();
    $shippingRate = $action->handle(StoreShippingRateInput::fromArray($data));

    expect($shippingRate)->toBeInstanceOf(ShippingRate::class)
        ->and($shippingRate->name)->toBe('Free Shipping')
        ->and($shippingRate->type)->toBe(ShippingRateType::Free)
        ->and($shippingRate->rate)->toBeNull();
});

test('creates an inactive shipping rate', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Inactive Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '5.9900',
        'is_active' => false,
    ];

    $action = new StoreShippingRateAction();
    $shippingRate = $action->handle(StoreShippingRateInput::fromArray($data));

    expect($shippingRate)->toBeInstanceOf(ShippingRate::class)
        ->and($shippingRate->name)->toBe('Inactive Rate')
        ->and($shippingRate->type)->toBe(ShippingRateType::Flat)
        ->and($shippingRate->rate)->toBe('5.9900')
        ->and($shippingRate->is_active)->toBeFalse();
});
