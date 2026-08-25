<?php

declare(strict_types=1);

use App\Actions\StoreCustomerAddressAction;
use App\Enums\Country;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CustomerAddressController;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

covers(CustomerAddressController::class, StoreCustomerAddressRequest::class, StoreCustomerAddressAction::class);

uses()->group('customer');

test('creates a new customer address', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
        'is_default' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.customers.addresses.store', $customer), $data);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('customer_addresses', [
        'user_id' => $customer->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
        'is_default' => true,
    ]);
});

test('validates required fields when creating address', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->post(route('admin.customers.addresses.store', $customer), []);

    $response->assertRedirectBack()
        ->assertInvalid(['first_name', 'last_name', 'address_line_1', 'city', 'postal_code', 'country_code']);
});

test('validates country code is valid', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->post(route('admin.customers.addresses.store', $customer), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'XX',
        'phone' => '+14155552671',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('country_code');
});

test('sets address as default and unsets other default addresses', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $existingAddress = CustomerAddress::factory()->forUser($customer)->default()->create();

    $data = [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address_line_1' => '456 Oak Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
        'is_default' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.customers.addresses.store', $customer), $data);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('customer_addresses', [
        'id' => $existingAddress->id,
        'is_default' => false,
    ]);

    assertDatabaseHas('customer_addresses', [
        'user_id' => $customer->id,
        'first_name' => 'Jane',
        'is_default' => true,
    ]);
});

test('requires authentication', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = post(route('admin.customers.addresses.store', $customer), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsAdmin()->post(route('admin.customers.addresses.store', $customer), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('customer_addresses', [
        'user_id' => $customer->id,
        'first_name' => 'John',
    ]);

    $role->revokePermissionTo(Permission::CustomersManage);

    $response = actingAsAdmin()->post(route('admin.customers.addresses.store', $customer), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address_line_1' => '456 Oak Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('customer_addresses', [
        'user_id' => $customer->id,
        'first_name' => 'Jane',
    ]);
});
