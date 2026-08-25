<?php

declare(strict_types=1);

use App\Actions\RecalculateCustomerLifetimeValueAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Requests\Admin\IndexCustomerRequest;
use App\Models\Order;
use App\Models\User;
use App\Queries\CustomerListQuery;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(CustomerController::class, CustomerListQuery::class, IndexCustomerRequest::class);

uses()->group('customer');

test('displays customers list page', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customers = User::factory(3)->create();
    foreach ($customers as $customer) {
        $customer->assignRole($customerRole);
    }

    $response = actingAsSuperAdmin()->get(route('admin.customers.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/customers/list')
                ->has('customers.data', 3)
                ->where('filters.query', null)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('only shows users with customer role', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);

    $customer1 = User::factory()->create(['name' => 'Customer One']);
    $customer1->assignRole($customerRole);

    $customer2 = User::factory()->create(['name' => 'Customer Two']);
    $customer2->assignRole($customerRole);

    $adminUser = User::factory()->create(['name' => 'Admin User']);
    $adminUser->assignRole($adminRole);

    $regularUser = User::factory()->create(['name' => 'Regular User']);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/customers/list')
                ->has('customers.data', 2)
                ->where('customers.data.0.name', 'Customer One')
                ->where('customers.data.1.name', 'Customer Two')
        );
});

test('includes customer order metrics', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customer = User::factory()->create([
        'name' => 'Orders Customer',
        'email' => 'orders@example.com',
    ]);
    $customer->assignRole($customerRole);

    Order::factory()->fulfilled()->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'total' => 120,
    ]);

    Order::factory()->fulfilled()->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'total' => 80,
    ]);

    app(RecalculateCustomerLifetimeValueAction::class)->handle($customer);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('customers.data.0.order_count', 2)
                ->where('customers.data.0.lifetime_value', '200.0000')
        );
});

test('filters customers by query', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customer1 = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $customer1->assignRole($customerRole);

    $customer2 = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    $customer2->assignRole($customerRole);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', ['query' => 'John']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.query', 'John')
        );
});

test('requires authentication', function () {
    $response = get(route('admin.customers.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.customers.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::CustomersView);

    $response = actingAsAdmin()->get(route('admin.customers.index'));

    $response->assertForbidden();
});

test('sorts customers by name in ascending order', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customerB = User::factory()->create(['name' => 'Beta Customer', 'email' => 'beta@example.com']);
    $customerB->assignRole($customerRole);

    $customerA = User::factory()->create(['name' => 'Alpha Customer', 'email' => 'alpha@example.com']);
    $customerA->assignRole($customerRole);

    $customerC = User::factory()->create(['name' => 'Gamma Customer', 'email' => 'gamma@example.com']);
    $customerC->assignRole($customerRole);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'sort' => 'name',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'asc')
                ->where('customers.data.0.name', 'Alpha Customer')
                ->where('customers.data.1.name', 'Beta Customer')
                ->where('customers.data.2.name', 'Gamma Customer')
        );
});

test('sorts customers by name in descending order', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customerB = User::factory()->create(['name' => 'Beta Customer', 'email' => 'beta@example.com']);
    $customerB->assignRole($customerRole);

    $customerA = User::factory()->create(['name' => 'Alpha Customer', 'email' => 'alpha@example.com']);
    $customerA->assignRole($customerRole);

    $customerC = User::factory()->create(['name' => 'Gamma Customer', 'email' => 'gamma@example.com']);
    $customerC->assignRole($customerRole);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'sort' => 'name',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'desc')
                ->where('customers.data.0.name', 'Gamma Customer')
                ->where('customers.data.1.name', 'Beta Customer')
                ->where('customers.data.2.name', 'Alpha Customer')
        );
});

test('sorts customers by email', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customerA = User::factory()->create(['name' => 'Customer A', 'email' => 'zeta@example.com']);
    $customerA->assignRole($customerRole);

    $customerB = User::factory()->create(['name' => 'Customer B', 'email' => 'alpha@example.com']);
    $customerB->assignRole($customerRole);

    $customerC = User::factory()->create(['name' => 'Customer C', 'email' => 'beta@example.com']);
    $customerC->assignRole($customerRole);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'sort' => 'email',
        'direction' => 'asc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.sort', 'email')
                ->where('filters.direction', 'asc')
                ->where('customers.data.0.email', 'alpha@example.com')
                ->where('customers.data.1.email', 'beta@example.com')
                ->where('customers.data.2.email', 'zeta@example.com')
        );
});

test('sorts customers by order_count', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $fewOrdersCustomer = User::factory()->create([
        'name' => 'Few Orders',
        'email' => 'few@example.com',
    ]);
    $fewOrdersCustomer->assignRole($customerRole);
    Order::factory()->create([
        'customer_id' => $fewOrdersCustomer->id,
        'customer_email' => $fewOrdersCustomer->email,
        'total' => 50,
    ]);

    $manyOrdersCustomer = User::factory()->create([
        'name' => 'Many Orders',
        'email' => 'many@example.com',
    ]);
    $manyOrdersCustomer->assignRole($customerRole);
    Order::factory()->count(2)->create([
        'customer_id' => $manyOrdersCustomer->id,
        'customer_email' => $manyOrdersCustomer->email,
        'total' => 75,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'sort' => 'order_count',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.sort', 'order_count')
                ->where('filters.direction', 'desc')
                ->where('customers.data.0.email', 'many@example.com')
                ->where('customers.data.0.order_count', 2)
                ->where('customers.data.1.email', 'few@example.com')
                ->where('customers.data.1.order_count', 1)
        );
});

test('sorts customers by lifetime_value', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $highValueCustomer = User::factory()->create([
        'name' => 'High Value',
        'email' => 'high@example.com',
    ]);
    $highValueCustomer->assignRole($customerRole);
    Order::factory()->fulfilled()->create([
        'customer_id' => $highValueCustomer->id,
        'customer_email' => $highValueCustomer->email,
        'total' => 300,
    ]);

    $lowerValueCustomer = User::factory()->create([
        'name' => 'Lower Value',
        'email' => 'low@example.com',
    ]);
    $lowerValueCustomer->assignRole($customerRole);
    Order::factory()->fulfilled()->create([
        'customer_id' => $lowerValueCustomer->id,
        'customer_email' => $lowerValueCustomer->email,
        'total' => 120,
    ]);

    $recalculateAction = app(RecalculateCustomerLifetimeValueAction::class);
    $recalculateAction->handle($highValueCustomer);
    $recalculateAction->handle($lowerValueCustomer);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'sort' => 'lifetime_value',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.sort', 'lifetime_value')
                ->where('filters.direction', 'desc')
                ->where('customers.data.0.email', 'high@example.com')
                ->where('customers.data.0.lifetime_value', '300.0000')
                ->where('customers.data.1.email', 'low@example.com')
                ->where('customers.data.1.lifetime_value', '120.0000')
        );
});

test('sorts customers by last_login_at', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customerA = User::factory()->create([
        'name' => 'Early Login Customer',
        'email' => 'early@example.com',
        'last_login_at' => now()->subDays(10),
    ]);
    $customerA->assignRole($customerRole);

    $customerB = User::factory()->create([
        'name' => 'Recent Login Customer',
        'email' => 'recent@example.com',
        'last_login_at' => now()->subDays(2),
    ]);
    $customerB->assignRole($customerRole);

    $customerC = User::factory()->create([
        'name' => 'Very Recent Customer',
        'email' => 'veryrecent@example.com',
        'last_login_at' => now()->subHours(12),
    ]);
    $customerC->assignRole($customerRole);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'sort' => 'last_login_at',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.sort', 'last_login_at')
                ->where('filters.direction', 'desc')
                ->where('customers.data.0.name', 'Very Recent Customer')
                ->where('customers.data.1.name', 'Recent Login Customer')
                ->where('customers.data.2.name', 'Early Login Customer')
        );
});

test('sorts customers by created_at', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customerA = User::factory()->create([
        'name' => 'Old Customer',
        'email' => 'old@example.com',
        'created_at' => now()->subDays(30),
    ]);
    $customerA->assignRole($customerRole);

    $customerB = User::factory()->create([
        'name' => 'New Customer',
        'email' => 'new@example.com',
        'created_at' => now()->subDays(1),
    ]);
    $customerB->assignRole($customerRole);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'sort' => 'created_at',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
                ->where('customers.data.0.name', 'New Customer')
                ->where('customers.data.1.name', 'Old Customer')
        );
});

test('paginates customers with custom per_page', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    // Create 20 customers
    $customers = User::factory()->count(20)->create();
    foreach ($customers as $customer) {
        $customer->assignRole($customerRole);
    }

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'per_page' => 5,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('customers.data', 5)
                ->has('customers.links')
                ->where('customers.per_page', 5)
        );
});

test('paginates customers with default per_page', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    // Create 20 customers
    $customers = User::factory()->count(20)->create();
    foreach ($customers as $customer) {
        $customer->assignRole($customerRole);
    }

    $response = actingAsSuperAdmin()->get(route('admin.customers.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('customers.data', 15) // Default per_page is 15
                ->has('customers.links')
                ->where('customers.per_page', 15)
        );
});

test('maintains filters when paginating', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customerA = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $customerA->assignRole($customerRole);

    $customerB = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $customerB->assignRole($customerRole);

    $response = actingAsSuperAdmin()->get(route('admin.customers.index', [
        'query' => 'Doe',
        'sort' => 'name',
        'direction' => 'asc',
        'per_page' => 1,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('filters.query', 'Doe')
                ->where('filters.sort', 'name')
                ->where('filters.direction', 'asc')
                ->where('customers.per_page', 1)
        );
});
