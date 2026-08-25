<?php

declare(strict_types=1);

use App\Actions\UpdateCustomerAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(CustomerController::class, UpdateCustomerRequest::class, UpdateCustomerAction::class, CustomerAddressListQuery::class);

uses()->group('customer');

test('can view customer edit page with addresses', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);
    $customer->assignRole(RoleEnum::Customer);

    $address1 = CustomerAddress::factory()->forUser($customer)->create([
        'created_at' => now()->subDays(3),
    ]);
    $address2 = CustomerAddress::factory()->forUser($customer)->create([
        'created_at' => now()->subDay(),
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.customers.edit', $customer));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/customers/edit')
                ->has('customer')
                ->where('customer.id', $customer->id)
                ->where('customer.name', $customer->name)
                ->has('addresses', 2)
                ->where('addresses.0.id', $address2->id)
                ->where('addresses.1.id', $address1->id)
        );
});

test('can view customer edit page with empty addresses', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->get(route('admin.customers.edit', $customer));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/customers/edit')
                ->has('customer')
                ->where('customer.id', $customer->id)
                ->has('addresses', 0)
        );
});

test('only displays addresses for the requested customer', function () {
    $customer1 = User::factory()->create(['email' => 'customer1@example.com']);
    $customer1->assignRole(RoleEnum::Customer);

    $customer2 = User::factory()->create(['email' => 'customer2@example.com']);
    $customer2->assignRole(RoleEnum::Customer);

    $address1 = CustomerAddress::factory()->forUser($customer1)->create([
        'created_at' => now()->subDays(2),
    ]);
    CustomerAddress::factory()->forUser($customer2)->create();
    $address2 = CustomerAddress::factory()->forUser($customer1)->create([
        'created_at' => now()->subDay(),
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.customers.edit', $customer1));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('addresses', 2)
                ->where('addresses.0.id', $address2->id)
                ->where('addresses.1.id', $address1->id)
        );
});

test('displays addresses ordered by latest first', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);
    $customer->assignRole(RoleEnum::Customer);

    $oldestAddress = CustomerAddress::factory()->forUser($customer)->create([
        'created_at' => now()->subDays(10),
    ]);
    $middleAddress = CustomerAddress::factory()->forUser($customer)->create([
        'created_at' => now()->subDays(5),
    ]);
    $newestAddress = CustomerAddress::factory()->forUser($customer)->create([
        'created_at' => now()->subDay(),
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.customers.edit', $customer));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('addresses', 3)
                ->where('addresses.0.id', $newestAddress->id)
                ->where('addresses.1.id', $middleAddress->id)
                ->where('addresses.2.id', $oldestAddress->id)
        );
});

test('can update customer name and email', function () {
    $customer = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $customer->refresh();
    expect($customer->name)->toBe('Updated Name')
        ->and($customer->email)->toBe('updated@example.com');

    assertDatabaseHas('users', [
        'id' => $customer->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('can update customer password when provided', function () {
    $customer = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => $customer->name,
        'email' => $customer->email,
        'password' => 'new-password',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $customer->refresh();
    expect(Hash::check('new-password', $customer->password))->toBeTrue()
        ->and(Hash::check('old-password', $customer->password))->toBeFalse();
});

test('does not update password when empty', function () {
    $originalPassword = Hash::make('original-password');
    $customer = User::factory()->create(['password' => $originalPassword]);
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'password' => '',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $customer->refresh();
    expect($customer->password)->toBe($originalPassword);
});

test('validates required fields when updating customer', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => '',
        'email' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'email']);
});

test('validates email uniqueness when updating customer', function () {
    $customer1 = User::factory()->create(['email' => 'customer1@example.com']);
    $customer1->assignRole(RoleEnum::Customer);

    $customer2 = User::factory()->create(['email' => 'customer2@example.com']);
    $customer2->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer1), [
        'name' => $customer1->name,
        'email' => 'customer2@example.com',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('email');

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer1), [
        'name' => 'Updated Name',
        'email' => 'customer1@example.com',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();
});

test('accepts an address in a postal_code-free country without a postal_code', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => $customer->name,
        'email' => $customer->email,
        'addresses' => [
            [
                'first_name' => 'Sara',
                'last_name' => 'Al Maktoum',
                'address_line_1' => '12 Marina Walk',
                'city' => 'Dubai',
                'state' => 'Abu Dhabi',
                'country_code' => 'AE',
                'phone' => '+14155552671',
            ],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertValid(['addresses.0.postal_code', 'addresses.0.state']);
});

test('rejects an address in a postal_code-required country without a postal_code', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => $customer->name,
        'email' => $customer->email,
        'addresses' => [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '1 Market St',
                'city' => 'San Francisco',
                'state' => 'CA',
                'country_code' => 'US',
                'phone' => '+14155552671',
            ],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['addresses.0.postal_code']);
});

test('rejects an address whose state is not a valid option for the country', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsSuperAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => $customer->name,
        'email' => $customer->email,
        'addresses' => [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '1 Market St',
                'city' => 'San Francisco',
                'state' => 'NOT_A_STATE',
                'postal_code' => '94103',
                'country_code' => 'US',
                'phone' => '+14155552671',
            ],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['addresses.0.state']);
});

test('requires authentication to view customer edit page', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = get(route('admin.customers.edit', $customer));

    $response->assertRedirect(route('admin.login'));
});

test('requires authentication to update customer', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = patch(route('admin.customers.update', $customer), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.update permission to view customer edit page', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsAdmin()->get(route('admin.customers.edit', $customer));

    $response->assertOk();

    $role->revokePermissionTo(Permission::CustomersManage);

    $response = actingAsAdmin()->get(route('admin.customers.edit', $customer));

    $response->assertForbidden();
});

test('requires customers.update permission to update customer', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $customer = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $customer->assignRole(RoleEnum::Customer);

    $response = actingAsAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertRedirectBack();

    assertDatabaseHas('users', [
        'id' => $customer->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $role->revokePermissionTo(Permission::CustomersManage);

    $response = actingAsAdmin()->patch(route('admin.customers.update', $customer), [
        'name' => 'Forbidden Name',
        'email' => 'forbidden@example.com',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('users', [
        'id' => $customer->id,
        'name' => 'Forbidden Name',
    ]);
});
