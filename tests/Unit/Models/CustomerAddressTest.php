<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(CustomerAddress::class);

uses()->group('models', 'customer');

test('has factory', function () {
    expect(CustomerAddress::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $address = CustomerAddress::factory()->create([
        'is_default' => true,
    ]);

    $casts = $address->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('is_default', 'boolean');
});

test('casts is_default as boolean correctly', function () {
    $address = CustomerAddress::factory()->create(['is_default' => true]);

    expect($address->is_default)->toBeBool()->toBeTrue();
});

test('handles is_default boolean false value', function () {
    $address = CustomerAddress::factory()->create(['is_default' => false]);

    expect($address->is_default)->toBeBool()->toBeFalse();
});

test('belongs to user relationship', function () {
    $user = User::factory()->create();
    $address = CustomerAddress::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($address->user)->toBeInstanceOf(User::class)
        ->and($address->user->id)->toBe($user->id);
});

test('handles address fields correctly', function () {
    $address = CustomerAddress::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '+1-555-123-4567',
    ]);

    expect($address->first_name)->toBe('John');
    expect($address->last_name)->toBe('Doe');
    expect($address->address_line_1)->toBe('123 Main St');
    expect($address->address_line_2)->toBe('Apt 4B');
    expect($address->city)->toBe('New York');
    expect($address->state)->toBe('NY');
    expect($address->postal_code)->toBe('10001');
    expect($address->country_code)->toBe('US');
    expect($address->phone)->toBe('+1-555-123-4567');
});

test('resolves the state code into a state name', function () {
    $address = CustomerAddress::factory()->create(['country_code' => 'US', 'state' => 'NY']);

    expect($address->state_name)->toBe('New York')
        ->and($address->toArray())->toHaveKey('state_name', 'New York');
});

test('handles nullable address_line_2', function () {
    $address = CustomerAddress::factory()->create([
        'address_line_2' => null,
    ]);

    expect($address->address_line_2)->toBeNull();
});

test('creates address with default values', function () {
    $address = CustomerAddress::factory()->forCountry('US')->create();

    expect($address->id)->toBeInt();
    expect($address->user_id)->toBeInt();
    expect($address->first_name)->toBeString();
    expect($address->last_name)->toBeString();
    expect($address->address_line_1)->toBeString();
    expect($address->city)->toBeString();
    expect($address->state)->toBeString();
    expect($address->postal_code)->toBeString();
    expect($address->country_code)->toBeString();
    expect($address->phone)->toBeString();
    expect($address->is_default)->toBeBool();
    expect($address->created_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
    expect($address->updated_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});
