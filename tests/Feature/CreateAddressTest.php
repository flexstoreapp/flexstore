<?php

declare(strict_types=1);

use App\Actions\StoreCustomerAddressAction;
use App\Http\Controllers\Storefront\AddressController;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Models\Setting;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use App\Rules\SellingCountryRule;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers([
    AddressController::class,
    StoreCustomerAddressAction::class,
    StoreCustomerAddressRequest::class,
    SellingCountryRule::class,
    CustomerAddressListQuery::class,
]);

uses()->group('account');

test('addresses list requires authentication', function () {
    get(route('account.addresses.index'))
        ->assertRedirect(route('account.login'));
});

test('addresses list page renders only the customer own addresses', function () {
    $user = User::factory()->create();
    CustomerAddress::factory()->count(2)->create(['user_id' => $user->id]);
    CustomerAddress::factory()->create(['user_id' => User::factory()->create()->id]);

    actingAs($user)
        ->get(route('account.addresses.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('storefront/account/addresses/list')
            ->has('addresses', 2));
});

test('creating address requires authentication', function () {
    post(route('account.addresses.store'), [])
        ->assertRedirect(route('account.login'));
});

test('address can be created', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ])
        ->assertRedirect();

    assertDatabaseHas('customer_addresses', [
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
    ]);
});

test('creating address rejects a country the store does not support', function () {
    Setting::setValue('selling_countries', ['BD']);

    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ])
        ->assertSessionHasErrors('country_code');
});

test('creating address rejects an invalid phone number', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => 'not-a-phone',
        ])
        ->assertInvalid('phone');
});

test('creating address accepts a valid international phone number', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => '+8801712345678',
        ])
        ->assertValid('phone');
});

test('creating address validates required fields', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), [])
        ->assertSessionHasErrors([
            'first_name',
            'last_name',
            'address_line_1',
            'city',
            'postal_code',
            'country_code',
        ]);
});

test('address can be created as default', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => '+14155552671',
            'is_default' => true,
        ])
        ->assertRedirect();

    assertDatabaseHas('customer_addresses', [
        'user_id' => $user->id,
        'is_default' => true,
    ]);
});

test('creating address rejects invalid country code', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('account.addresses.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'XX',
            'phone' => '+14155552671',
        ])
        ->assertSessionHasErrors('country_code');
});
