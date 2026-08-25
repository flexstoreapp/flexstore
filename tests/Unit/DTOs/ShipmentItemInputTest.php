<?php

declare(strict_types=1);

use App\DTOs\ShipmentItemInput;

covers(ShipmentItemInput::class);

uses()->group('dtos', 'shipment');

test('stores the order item and quantity', function () {
    $input = new ShipmentItemInput(orderItemId: 7, quantity: 2);

    expect($input->orderItemId)->toBe(7)
        ->and($input->quantity)->toBe(2);
});

test('fromArray casts the scalars to integers', function () {
    $input = ShipmentItemInput::fromArray(['order_item_id' => '7', 'quantity' => '2']);

    expect($input->orderItemId)->toBe(7)
        ->and($input->quantity)->toBe(2);
});
