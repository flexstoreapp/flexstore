<?php

declare(strict_types=1);

use App\Actions\UpsertOrderAddressAction;
use App\DTOs\Address;
use App\Enums\OrderAddressType;
use App\Models\Order;
use App\Models\OrderAddress;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

covers(UpsertOrderAddressAction::class);

uses()->group('actions', 'order');

test('creates a new billing address for an order', function () {
    $order = Order::factory()->create();

    $addressData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '5551234567',
    ];

    $result = app(UpsertOrderAddressAction::class)->handle(
        $order,
        Address::fromArray($addressData),
        OrderAddressType::Billing
    );

    expect($result)->toBeInstanceOf(OrderAddress::class)
        ->and($result->order_id)->toBe($order->id)
        ->and($result->type)->toBe(OrderAddressType::Billing)
        ->and($result->first_name)->toBe('John')
        ->and($result->last_name)->toBe('Doe')
        ->and($result->address_line_1)->toBe('123 Main St')
        ->and($result->address_line_2)->toBe('Apt 4B')
        ->and($result->city)->toBe('New York')
        ->and($result->state)->toBe('NY')
        ->and($result->postal_code)->toBe('10001')
        ->and($result->country_code)->toBe('US')
        ->and($result->phone)->toBe('5551234567');

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});

test('creates a new shipping address for an order', function () {
    $order = Order::factory()->create();

    $addressData = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '456 Oak Ave',
        'address_line_2' => null,
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country_code' => 'US',
        'phone' => null,
    ];

    $result = app(UpsertOrderAddressAction::class)->handle(
        $order,
        Address::fromArray($addressData),
        OrderAddressType::Shipping
    );

    expect($result)->toBeInstanceOf(OrderAddress::class)
        ->and($result->type)->toBe(OrderAddressType::Shipping)
        ->and($result->first_name)->toBe('Jane')
        ->and($result->address_line_2)->toBeNull()
        ->and($result->phone)->toBeNull();

    assertDatabaseCount('order_addresses', 1);
});

test('updates an existing billing address for an order', function () {
    $order = Order::factory()->create();
    $existingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing,
        'first_name' => 'Old',
        'last_name' => 'Address',
    ]);

    $updatedData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => null,
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '5551234567',
    ];

    $result = app(UpsertOrderAddressAction::class)->handle(
        $order,
        Address::fromArray($updatedData),
        OrderAddressType::Billing
    );

    expect($result->id)->toBe($existingAddress->id)
        ->and($result->first_name)->toBe('John')
        ->and($result->last_name)->toBe('Doe')
        ->and($result->city)->toBe('New York');

    assertDatabaseCount('order_addresses', 1);
    assertDatabaseHas('order_addresses', [
        'id' => $existingAddress->id,
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
        'first_name' => 'John',
    ]);
});

test('handles multiple addresses for an order', function () {
    $order = Order::factory()->create();

    $billingData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => null,
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '5551234567',
    ];

    $shippingData = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '456 Oak Ave',
        'address_line_2' => null,
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country_code' => 'US',
        'phone' => null,
    ];

    $billing = app(UpsertOrderAddressAction::class)->handle(
        $order,
        Address::fromArray($billingData),
        OrderAddressType::Billing
    );

    $shipping = app(UpsertOrderAddressAction::class)->handle(
        $order,
        Address::fromArray($shippingData),
        OrderAddressType::Shipping
    );

    expect($billing->type)->toBe(OrderAddressType::Billing)
        ->and($shipping->type)->toBe(OrderAddressType::Shipping)
        ->and($billing->first_name)->toBe('John')
        ->and($shipping->first_name)->toBe('Jane');

    assertDatabaseCount('order_addresses', 2);
});

test('preserves optional fields as null when not provided', function () {
    $order = Order::factory()->create();

    $minimalData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => null,
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => null,
    ];

    $result = app(UpsertOrderAddressAction::class)->handle(
        $order,
        Address::fromArray($minimalData),
        OrderAddressType::Billing
    );

    expect($result->address_line_2)->toBeNull()
        ->and($result->phone)->toBeNull();

    assertDatabaseHas('order_addresses', [
        'id' => $result->id,
        'address_line_2' => null,
        'phone' => null,
    ]);
});
