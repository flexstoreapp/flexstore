<?php

declare(strict_types=1);

use App\Actions\UpdateShippingCarrierAction;
use App\DTOs\UpdateShippingCarrierInput;
use App\Enums\ShippingCarrierDriver;
use App\Models\ShippingCarrier;

covers(UpdateShippingCarrierAction::class, UpdateShippingCarrierInput::class);

uses()->group('actions', 'shipping');

test('updates a shipping carrier', function () {
    $carrier = ShippingCarrier::factory()->create([
        'name' => 'Old Carrier',
        'driver' => ShippingCarrierDriver::Manual,
        'is_active' => true,
    ]);

    $data = [
        'name' => 'Updated Carrier',
        'is_active' => true,
    ];

    $action = new UpdateShippingCarrierAction();
    $updatedCarrier = $action->handle($carrier, UpdateShippingCarrierInput::fromArray($data));

    expect($updatedCarrier->id)->toBe($carrier->id)
        ->and($updatedCarrier->name)->toBe('Updated Carrier')
        ->and($updatedCarrier->driver)->toBe(ShippingCarrierDriver::Manual)
        ->and($updatedCarrier->is_active)->toBeTrue();
});

test('marks a shipping carrier inactive', function () {
    $carrier = ShippingCarrier::factory()->create([
        'is_active' => true,
    ]);

    $data = [
        'name' => $carrier->name,
        'is_active' => false,
    ];

    $action = new UpdateShippingCarrierAction();
    $updatedCarrier = $action->handle($carrier, UpdateShippingCarrierInput::fromArray($data));

    expect($updatedCarrier->id)->toBe($carrier->id)
        ->and($updatedCarrier->is_active)->toBeFalse();
});
