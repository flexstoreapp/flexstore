<?php

declare(strict_types=1);

use App\Actions\UpdateCustomerAddressAction;
use App\Enums\Country;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CustomerAddressController;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\patch;

covers(CustomerAddressController::class, UpdateCustomerAddressRequest::class, UpdateCustomerAddressAction::class);

uses()->group('customer');

test('updates an existing customer address', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.addresses.update', [$customer, $address]), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '789 Pine St',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('customer_addresses', [
        'id' => $address->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '789 Pine St',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'phone' => '+14155552671',
    ]);
});

test('validates required fields when updating address', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $response = actingAsSuperAdmin()->patch(route('admin.customers.addresses.update', [$customer, $address]), [
        'first_name' => '',
        'last_name' => '',
        'address_line_1' => '',
        'city' => '',
        'state' => '',
        'postal_code' => '',
        'country_code' => '',
        'phone' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['first_name', 'last_name', 'address_line_1', 'city', 'postal_code', 'country_code']);
});

test('requires authentication', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $response = patch(route('admin.customers.addresses.update', [$customer, $address]), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '789 Pine St',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $response = actingAsAdmin()->patch(route('admin.customers.addresses.update', [$customer, $address]), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '789 Pine St',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('customer_addresses', [
        'id' => $address->id,
        'first_name' => 'Jane',
    ]);

    $role->revokePermissionTo(Permission::CustomersManage);

    $anotherAddress = CustomerAddress::factory()->forUser($customer)->create();

    $response = actingAsAdmin()->patch(route('admin.customers.addresses.update', [$customer, $anotherAddress]), [
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'address_line_1' => '123 Test St',
        'city' => 'Test City',
        'state' => 'TC',
        'postal_code' => '12345',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('customer_addresses', [
        'id' => $anotherAddress->id,
        'first_name' => 'Updated',
    ]);
});

test('prevents updating address that belongs to different customer', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($otherCustomer)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.addresses.update', [$customer, $address]), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '789 Pine St',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertForbidden();

    assertDatabaseHas('customer_addresses', [
        'id' => $address->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});
