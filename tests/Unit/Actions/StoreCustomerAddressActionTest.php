<?php

declare(strict_types=1);

use App\Actions\StoreCustomerAddressAction;
use App\DTOs\StoreCustomerAddressInput;
use App\Enums\Country;
use App\Enums\Role as RoleEnum;
use App\Models\CustomerAddress;
use App\Models\User;

covers(StoreCustomerAddressAction::class, StoreCustomerAddressInput::class);

uses()->group('actions', 'customer');

test('creates a customer address with required fields', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => Country::US->name,
        'phone' => '1234567890',
    ];

    $action = new StoreCustomerAddressAction();
    $result = $action->handle($customer, StoreCustomerAddressInput::fromArray($data));

    expect($result)->toBeInstanceOf(CustomerAddress::class)
        ->and($result->user_id)->toBe($customer->id)
        ->and($result->first_name)->toBe('John')
        ->and($result->last_name)->toBe('Doe')
        ->and($result->address_line_1)->toBe('123 Main St')
        ->and($result->city)->toBe('New York')
        ->and($result->state)->toBe('NY')
        ->and($result->postal_code)->toBe('10001')
        ->and($result->country_code)->toBe(Country::US->name)
        ->and($result->phone)->toBe('1234567890')
        ->and($result->is_default)->toBeFalse()
        ->and(CustomerAddress::query()->where('user_id', $customer->id)->count())->toBe(1);
});

test('creates a customer address with optional fields', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $data = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '456 Oak Ave',
        'address_line_2' => 'Apt 4B',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country_code' => Country::US->name,
        'phone' => '0987654321',
        'is_default' => true,
    ];

    $action = new StoreCustomerAddressAction();
    $result = $action->handle($customer, StoreCustomerAddressInput::fromArray($data));

    expect($result->address_line_2)->toBe('Apt 4B')
        ->and($result->is_default)->toBeTrue();
});

test('sets address as default and unsets other default addresses', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $existingAddress = CustomerAddress::factory()->forUser($customer)->default()->create();

    $data = [
        'first_name' => 'New',
        'last_name' => 'User',
        'address_line_1' => '789 Pine St',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'country_code' => Country::US->name,
        'phone' => '5551234567',
        'is_default' => true,
    ];

    $action = new StoreCustomerAddressAction();
    $result = $action->handle($customer, StoreCustomerAddressInput::fromArray($data));

    expect($result->is_default)->toBeTrue();

    $existingAddress->refresh();
    expect($existingAddress->is_default)->toBeFalse();
});
