<?php

declare(strict_types=1);

use App\Address\AddressFieldRules;
use App\Http\Controllers\Storefront\AddressController;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

covers(AddressController::class, StoreCustomerAddressRequest::class, AddressFieldRules::class);

uses()->group('address', 'customer');

function customerAddressPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'Dhaka',
        'state' => 'Dhaka',
        'postal_code' => '1207',
        'country_code' => 'BD',
        'phone' => '+14155552671',
    ], $overrides);
}

it('accepts a Bangladesh address without a postal_code', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), customerAddressPayload([
            'country_code' => 'BD',
            'state' => 'Dhaka',
            'postal_code' => '',
        ]))
        ->assertSessionHasNoErrors();

    assertDatabaseHas('customer_addresses', [
        'user_id' => $user->id,
        'country_code' => 'BD',
        'state' => 'Dhaka',
        'postal_code' => null,
    ]);
});

it('rejects a US address without a postal_code', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), customerAddressPayload([
            'country_code' => 'US',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '',
        ]))
        ->assertSessionHasErrors('postal_code');
});

it('rejects an invalid Bangladesh district', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), customerAddressPayload([
            'country_code' => 'BD',
            'state' => 'Atlantis',
            'postal_code' => '',
        ]))
        ->assertSessionHasErrors('state');
});

it('stores a valid US state code', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), customerAddressPayload([
            'country_code' => 'US',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]))
        ->assertSessionHasNoErrors();

    assertDatabaseHas('customer_addresses', [
        'user_id' => $user->id,
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);
});

it('rejects an invalid US state code', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), customerAddressPayload([
            'country_code' => 'US',
            'city' => 'New York',
            'state' => 'ZZ',
            'postal_code' => '10001',
        ]))
        ->assertSessionHasErrors('state');
});
