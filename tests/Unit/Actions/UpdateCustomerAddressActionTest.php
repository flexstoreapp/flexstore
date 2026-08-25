<?php

declare(strict_types=1);

use App\Actions\UpdateCustomerAddressAction;
use App\DTOs\UpdateCustomerAddressInput;
use App\Enums\Country;
use App\Enums\Role as RoleEnum;
use App\Models\CustomerAddress;
use App\Models\User;

covers(UpdateCustomerAddressAction::class, UpdateCustomerAddressInput::class);

uses()->group('actions', 'customer');

test('updates a customer address with provided fields', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'city' => 'New York',
    ]);

    $data = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country_code' => Country::US->name,
        'phone' => '0987654321',
    ];

    $action = new UpdateCustomerAddressAction();
    $result = $action->handle($address, UpdateCustomerAddressInput::fromArray($data));

    expect($result)->toBeInstanceOf(CustomerAddress::class)
        ->and($result->first_name)->toBe('Jane')
        ->and($result->last_name)->toBe('Smith')
        ->and($result->city)->toBe('Los Angeles')
        ->and($result->state)->toBe('CA')
        ->and($result->postal_code)->toBe('90001')
        ->and($result->phone)->toBe('0987654321');

    $address->refresh();
    expect($address->first_name)->toBe('Jane')
        ->and($address->city)->toBe('Los Angeles');
});

test('updates a customer address with only specified fields', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'city' => 'New York',
        'state' => 'NY',
    ]);

    $data = [
        'first_name' => 'Jane',
    ];

    $action = new UpdateCustomerAddressAction();
    $result = $action->handle($address, UpdateCustomerAddressInput::fromArray($data));

    expect($result->first_name)->toBe('Jane')
        ->and($result->last_name)->toBe('Doe')
        ->and($result->city)->toBe('New York')
        ->and($result->state)->toBe('NY');

    $address->refresh();
    expect($address->first_name)->toBe('Jane')
        ->and($address->last_name)->toBe('Doe');
});

test('sets address as default and unsets other default addresses', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $existingDefault = CustomerAddress::factory()->forUser($customer)->default()->create();
    $address = CustomerAddress::factory()->forUser($customer)->create(['is_default' => false]);

    $data = [
        'is_default' => true,
    ];

    $action = new UpdateCustomerAddressAction();
    $result = $action->handle($address, UpdateCustomerAddressInput::fromArray($data));

    expect($result->is_default)->toBeTrue();

    $existingDefault->refresh();
    expect($existingDefault->is_default)->toBeFalse();
});

test('updates optional fields to null', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create([
        'address_line_2' => 'Apt 4B',
    ]);

    $data = [
        'address_line_2' => null,
    ];

    $action = new UpdateCustomerAddressAction();
    $result = $action->handle($address, UpdateCustomerAddressInput::fromArray($data));

    expect($result->address_line_2)->toBeNull();
});
