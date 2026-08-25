<?php

declare(strict_types=1);

use App\Actions\SetDefaultCustomerAddressAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\SetDefaultCustomerAddressController;
use App\Models\CustomerAddress;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(SetDefaultCustomerAddressController::class, SetDefaultCustomerAddressAction::class);

uses()->group('customer');

test('sets a customer address as default', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $current = CustomerAddress::factory()->forUser($customer)->create(['is_default' => true]);
    $address = CustomerAddress::factory()->forUser($customer)->create(['is_default' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.customers.addresses.default', [$customer, $address]));

    $response->assertRedirectBack();

    assertDatabaseHas('customer_addresses', ['id' => $address->id, 'is_default' => true]);
    assertDatabaseHas('customer_addresses', ['id' => $current->id, 'is_default' => false]);
});

test('prevents setting default for address that belongs to different customer', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($otherCustomer)->create(['is_default' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.customers.addresses.default', [$customer, $address]));

    $response->assertForbidden();

    assertDatabaseHas('customer_addresses', ['id' => $address->id, 'is_default' => false]);
});

test('requires authentication', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $response = post(route('admin.customers.addresses.default', [$customer, $address]));

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.manage permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create(['is_default' => false]);

    $response = actingAsAdmin()->post(route('admin.customers.addresses.default', [$customer, $address]));

    $response->assertRedirectBack();

    assertDatabaseHas('customer_addresses', ['id' => $address->id, 'is_default' => true]);

    $role->revokePermissionTo(Permission::CustomersManage);

    $anotherAddress = CustomerAddress::factory()->forUser($customer)->create(['is_default' => false]);

    $response = actingAsAdmin()->post(route('admin.customers.addresses.default', [$customer, $anotherAddress]));

    $response->assertForbidden();

    assertDatabaseHas('customer_addresses', ['id' => $anotherAddress->id, 'is_default' => false]);
});
