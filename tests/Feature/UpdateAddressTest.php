<?php

declare(strict_types=1);

use App\Actions\UpdateCustomerAddressAction;
use App\Http\Controllers\Storefront\AddressController;
use App\Http\Controllers\Storefront\SetDefaultAddressController;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Models\Setting;
use App\Models\User;
use App\Rules\SellingCountryRule;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

covers([
    AddressController::class,
    SetDefaultAddressController::class,
    UpdateCustomerAddressAction::class,
    UpdateCustomerAddressRequest::class,
    SellingCountryRule::class,
]);

uses()->group('account');

test('updating address requires authentication', function () {
    $address = CustomerAddress::factory()->create();

    patch(route('account.addresses.update', $address), [])
        ->assertRedirect(route('account.login'));
});

test('address can be updated', function () {
    $user = User::factory()->create();
    $address = CustomerAddress::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->patch(route('account.addresses.update', $address), [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90001',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ])
        ->assertRedirect();

    assertDatabaseHas('customer_addresses', [
        'id' => $address->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
    ]);
});

test('updating address rejects a country the store does not support', function () {
    Setting::setValue('selling_countries', ['BD']);

    $user = User::factory()->create();
    $address = CustomerAddress::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->patch(route('account.addresses.update', $address), [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90001',
            'country_code' => 'US',
        ])
        ->assertSessionHasErrors('country_code');
});

test('updating other users address is forbidden', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $address = CustomerAddress::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)
        ->patch(route('account.addresses.update', $address), [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90001',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ])
        ->assertForbidden();
});

test('deleting address requires authentication', function () {
    $address = CustomerAddress::factory()->create();

    delete(route('account.addresses.destroy', $address))
        ->assertRedirect(route('account.login'));
});

test('address can be deleted', function () {
    $user = User::factory()->create();
    $address = CustomerAddress::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->delete(route('account.addresses.destroy', $address))
        ->assertRedirect();

    assertDatabaseMissing('customer_addresses', [
        'id' => $address->id,
    ]);
});

test('deleting other users address is forbidden', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $address = CustomerAddress::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)
        ->delete(route('account.addresses.destroy', $address))
        ->assertForbidden();
});

test('setting default address requires authentication', function () {
    $address = CustomerAddress::factory()->create();

    post(route('account.addresses.default', $address))
        ->assertRedirect(route('account.login'));
});

test('address can be set as default', function () {
    $user = User::factory()->create();
    $address1 = CustomerAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $address2 = CustomerAddress::factory()->create(['user_id' => $user->id, 'is_default' => false]);

    actingAs($user)
        ->post(route('account.addresses.default', $address2))
        ->assertRedirect();

    assertDatabaseHas('customer_addresses', [
        'id' => $address1->id,
        'is_default' => false,
    ]);

    assertDatabaseHas('customer_addresses', [
        'id' => $address2->id,
        'is_default' => true,
    ]);
});

test('setting default on other users address is forbidden', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $address = CustomerAddress::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)
        ->post(route('account.addresses.default', $address))
        ->assertForbidden();
});
