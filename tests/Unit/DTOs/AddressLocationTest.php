<?php

declare(strict_types=1);

use App\DTOs\AddressLocation;

covers(AddressLocation::class);

uses()->group('dtos', 'tax');

test('stores all fields', function () {
    $address = new AddressLocation(
        countryCode: 'US',
        state: 'CA',
        postalCode: '90210',
    );

    expect($address->countryCode)->toBe('US')
        ->and($address->state)->toBe('CA')
        ->and($address->postalCode)->toBe('90210');
});

test('fromArray maps all fields', function () {
    $address = AddressLocation::fromArray([
        'country_code' => 'GB',
        'state' => 'England',
        'postal_code' => 'SW1A 2AA',
    ]);

    expect($address->countryCode)->toBe('GB')
        ->and($address->state)->toBe('England')
        ->and($address->postalCode)->toBe('SW1A 2AA');
});

test('fromArray defaults missing keys to empty string', function () {
    $address = AddressLocation::fromArray([]);

    expect($address->countryCode)->toBe('')
        ->and($address->state)->toBe('')
        ->and($address->postalCode)->toBe('');
});

test('fromArray handles partial data', function () {
    $address = AddressLocation::fromArray([
        'country_code' => 'CA',
    ]);

    expect($address->countryCode)->toBe('CA')
        ->and($address->state)->toBe('')
        ->and($address->postalCode)->toBe('');
});

test('properties are readonly', function () {
    $address = new AddressLocation('US', 'CA', '90210');

    expect(fn () => $address->countryCode = 'GB')->toThrow(Error::class);
});
