<?php

declare(strict_types=1);

use App\Actions\StoreCustomerAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(CustomerController::class, StoreCustomerRequest::class, StoreCustomerAction::class);

uses()->group('customer');

test('displays customer creation form', function () {
    $response = actingAsSuperAdmin()->get(route('admin.customers.create'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/customers/create')
        );
});

test('creates new customer successfully', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customerData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.customers.store'), $customerData);

    $response->assertSessionHasNoErrors();

    $customer = User::where('email', 'john@example.com')->first();
    $response->assertRedirect(route('admin.customers.edit', $customer));

    assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    expect($customer->hasRole(RoleEnum::Customer))->toBeTrue();
});

test('validates required fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.customers.store'), []);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'email', 'password']);
});

test('validates email uniqueness', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    User::factory()->create(['email' => 'existing@example.com']);

    $customerData = [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.customers.store'), $customerData);

    $response->assertRedirectBack()
        ->assertInvalid('email');
});

test('validates email format', function () {
    $customerData = [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.customers.store'), $customerData);

    $response->assertRedirectBack()
        ->assertInvalid('email');
});

test('validates password strength', function () {
    $customerData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => '123', // Too weak
    ];

    $response = actingAsSuperAdmin()->post(route('admin.customers.store'), $customerData);

    $response->assertRedirectBack()
        ->assertInvalid('password');
});

test('redirects back when add_more is true', function () {
    $customerData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'add_more' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.customers.store'), $customerData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);
});

test('requires authentication', function () {
    $response = get(route('admin.customers.create'));

    $response->assertRedirect(route('admin.login'));

    $response = post(route('admin.customers.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $customerData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $response = actingAsAdmin()->get(route('admin.customers.create'));

    $response->assertOk();

    $response = actingAsAdmin()->post(route('admin.customers.store'), $customerData);

    $response->assertSessionHasNoErrors();

    assertDatabaseHas('users', [
        'email' => 'john@example.com',
    ]);

    $role->revokePermissionTo(Permission::CustomersManage);

    $response = actingAsAdmin()->get(route('admin.customers.create'));

    $response->assertForbidden();

    $response = actingAsAdmin()->post(route('admin.customers.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('users', [
        'email' => 'jane@example.com',
    ]);
});
