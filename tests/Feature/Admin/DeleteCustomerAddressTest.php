<?php

declare(strict_types=1);

use App\Actions\DestroyCustomerAddressAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CustomerAddressController;
use App\Models\CustomerAddress;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(CustomerAddressController::class, DestroyCustomerAddressAction::class);

uses()->group('customer');

test('deletes a customer address', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $response = actingAsSuperAdmin()->delete(route('admin.customers.addresses.destroy', [$customer, $address]));

    $response->assertRedirectBack();

    assertDatabaseMissing('customer_addresses', [
        'id' => $address->id,
    ]);
});

test('prevents deleting address that belongs to different customer', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($otherCustomer)->create();

    $response = actingAsSuperAdmin()->delete(route('admin.customers.addresses.destroy', [$customer, $address]));

    $response->assertForbidden();

    assertDatabaseHas('customer_addresses', [
        'id' => $address->id,
    ]);
});

test('requires authentication', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $response = delete(route('admin.customers.addresses.destroy', [$customer, $address]));

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $response = actingAsAdmin()->delete(route('admin.customers.addresses.destroy', [$customer, $address]));

    $response->assertRedirectBack();

    assertDatabaseMissing('customer_addresses', [
        'id' => $address->id,
    ]);

    $role->revokePermissionTo(Permission::CustomersManage);

    $anotherAddress = CustomerAddress::factory()->forUser($customer)->create();

    $response = actingAsAdmin()->delete(route('admin.customers.addresses.destroy', [$customer, $anotherAddress]));

    $response->assertForbidden();

    assertDatabaseHas('customer_addresses', [
        'id' => $anotherAddress->id,
    ]);
});
