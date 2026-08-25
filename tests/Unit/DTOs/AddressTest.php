<?php

declare(strict_types=1);

use App\DTOs\Address;

covers(Address::class);

uses()->group('dtos', 'address');

test('stores all fields', function () {
    $address = new Address(
        firstName: 'Ada',
        lastName: 'Lovelace',
        addressLine1: '1 Analytical Engine Way',
        addressLine2: 'Apt 1815',
        city: 'London',
        state: 'England',
        postalCode: 'SW1A 2AA',
        countryCode: 'GB',
        phone: '+44 20 1234 5678',
    );

    expect($address->firstName)->toBe('Ada')
        ->and($address->addressLine1)->toBe('1 Analytical Engine Way')
        ->and($address->countryCode)->toBe('GB');
});

test('fromArray maps all fields', function () {
    $address = Address::fromArray([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'address_line_1' => '1 Analytical Engine Way',
        'address_line_2' => 'Apt 1815',
        'city' => 'London',
        'state' => 'England',
        'postal_code' => 'SW1A 2AA',
        'country_code' => 'GB',
        'phone' => '+44 20 1234 5678',
    ]);

    expect($address->firstName)->toBe('Ada')
        ->and($address->addressLine2)->toBe('Apt 1815')
        ->and($address->phone)->toBe('+44 20 1234 5678')
        ->and($address->postalCode)->toBe('SW1A 2AA');
});

test('fromArray treats nullable fields as null when missing', function () {
    $address = Address::fromArray([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'address_line_1' => '1 Compiler Ave',
        'city' => 'Arlington',
        'state' => 'VA',
        'postal_code' => '22201',
        'country_code' => 'US',
    ]);

    expect($address->addressLine2)->toBeNull()
        ->and($address->phone)->toBeNull();
});

test('fromArray treats empty or missing state and postal_code as null', function () {
    $address = Address::fromArray([
        'first_name' => 'Aisha',
        'last_name' => 'Rahman',
        'address_line_1' => 'House 1, Road 2',
        'city' => 'Dubai',
        'state' => '',
        'country_code' => 'AE',
    ]);

    expect($address->state)->toBeNull()
        ->and($address->postalCode)->toBeNull();
});

test('fromArray and toArray round-trip the email', function () {
    $address = Address::fromArray([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'address_line_1' => '1 Analytical Engine Way',
        'city' => 'London',
        'country_code' => 'GB',
        'email' => 'ada@example.com',
    ]);

    expect($address->email)->toBe('ada@example.com')
        ->and($address->toArray())->toHaveKey('email', 'ada@example.com');
});

test('fromArray treats a missing email as null', function () {
    $address = Address::fromArray([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'address_line_1' => '1 Compiler Ave',
        'city' => 'Arlington',
        'country_code' => 'US',
    ]);

    expect($address->email)->toBeNull();
});

test('toArray includes nullable fields', function () {
    $address = new Address(
        firstName: 'Aisha',
        lastName: 'Rahman',
        addressLine1: 'House 1, Road 2',
        addressLine2: null,
        city: 'Dubai',
        state: null,
        postalCode: null,
        countryCode: 'AE',
        phone: null,
    );

    expect($address->toArray())->toMatchArray([
        'city' => 'Dubai',
        'state' => null,
        'postal_code' => null,
        'country_code' => 'AE',
    ]);
});
