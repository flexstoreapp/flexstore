<?php

declare(strict_types=1);

use App\Actions\StoreShippingCarrierAction;
use App\DTOs\StoreShippingCarrierInput;
use App\Enums\ShippingCarrierDriver;
use App\Models\ShippingCarrier;

covers(StoreShippingCarrierAction::class, StoreShippingCarrierInput::class);

uses()->group('actions', 'shipping');

test('creates a shipping carrier', function () {
    $data = [
        'name' => 'Test Carrier',
        'driver' => ShippingCarrierDriver::Manual,
        'is_active' => true,
    ];

    $action = new StoreShippingCarrierAction();
    $carrier = $action->handle(StoreShippingCarrierInput::fromArray($data));

    expect($carrier)->toBeInstanceOf(ShippingCarrier::class)
        ->and($carrier->name)->toBe('Test Carrier')
        ->and($carrier->driver)->toBe(ShippingCarrierDriver::Manual)
        ->and($carrier->is_active)->toBeTrue();
});

test('creates an inactive shipping carrier', function () {
    $data = [
        'name' => 'Inactive Carrier',
        'driver' => ShippingCarrierDriver::Manual,
        'is_active' => false,
    ];

    $action = new StoreShippingCarrierAction();
    $carrier = $action->handle(StoreShippingCarrierInput::fromArray($data));

    expect($carrier)->toBeInstanceOf(ShippingCarrier::class)
        ->and($carrier->name)->toBe('Inactive Carrier')
        ->and($carrier->driver)->toBe(ShippingCarrierDriver::Manual)
        ->and($carrier->is_active)->toBeFalse();
});
