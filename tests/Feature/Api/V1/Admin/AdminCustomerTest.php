<?php

declare(strict_types=1);

use App\Enums\Country;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Api\V1\Admin\BulkCustomerController;
use App\Http\Controllers\Api\V1\Admin\CustomerAddressController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\SetDefaultCustomerAddressController;
use App\Http\Controllers\Api\V1\Admin\UserSearchController;
use App\Http\Middleware\Api\EnsureCustomerIsNotStaff;
use App\Http\Requests\Api\V1\Admin\SearchUserRequest;
use App\Http\Requests\Api\V1\Admin\StoreCustomerRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\Admin\CustomerListResource;
use App\Http\Resources\Api\V1\Admin\CustomerResource;
use App\Http\Resources\Api\V1\Admin\UserOptionResource;
use App\Models\CustomerAddress;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(
    EnsureCustomerIsNotStaff::class,
    CustomerController::class,
    BulkCustomerController::class,
    CustomerAddressController::class,
    SetDefaultCustomerAddressController::class,
    UserSearchController::class,
    StoreCustomerRequest::class,
    UpdateCustomerRequest::class,
    SearchUserRequest::class,
    CustomerResource::class,
    CustomerListResource::class,
    UserOptionResource::class,
);

uses()->group('api', 'admin-api');

beforeEach(function (): void {
    if (Illuminate\Support\Facades\Route::has('api.v1.admin.customers.index')) {
        return;
    }

    Illuminate\Support\Facades\Route::prefix('api/v1/admin')
        ->name('api.v1.admin.')
        ->middleware(['api', 'auth:sanctum', 'ability:' . App\Enums\TokenAbility::Admin->value])
        ->group(function (): void {
            $c = CustomerController::class;
            Illuminate\Support\Facades\Route::get('customers', [$c, 'index'])->middleware(Illuminate\Auth\Middleware\Authorize::using(Permission::CustomersView))->name('customers.index');
            Illuminate\Support\Facades\Route::get('customers/{customer}', [$c, 'show'])->middleware(Illuminate\Auth\Middleware\Authorize::using(Permission::CustomersView))->name('customers.show');
            Illuminate\Support\Facades\Route::get('customers/{customer}/addresses', [CustomerAddressController::class, 'index'])->middleware(Illuminate\Auth\Middleware\Authorize::using(Permission::CustomersView))->name('customers.addresses.index');
            Illuminate\Support\Facades\Route::middleware(Illuminate\Auth\Middleware\Authorize::using(Permission::CustomersManage))->group(function () use ($c): void {
                Illuminate\Support\Facades\Route::post('customers', [$c, 'store'])->name('customers.store');
                Illuminate\Support\Facades\Route::patch('customers/{customer}', [$c, 'update'])->name('customers.update');
                Illuminate\Support\Facades\Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('customers.addresses.store');
                Illuminate\Support\Facades\Route::patch('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customers.addresses.update');
                Illuminate\Support\Facades\Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy');
                Illuminate\Support\Facades\Route::post('customers/{customer}/addresses/{address}/default', SetDefaultCustomerAddressController::class)->name('customers.addresses.default');
            });
            Illuminate\Support\Facades\Route::middleware(Illuminate\Auth\Middleware\Authorize::using(Permission::CustomersDelete))->group(function () use ($c): void {
                Illuminate\Support\Facades\Route::delete('customers/bulk', [BulkCustomerController::class, 'destroy'])->name('customers.bulk.destroy');
                Illuminate\Support\Facades\Route::delete('customers/{customer}', [$c, 'destroy'])->name('customers.destroy');
            });
            Illuminate\Support\Facades\Route::get('users/search', UserSearchController::class)->middleware(Illuminate\Auth\Middleware\Authorize::using(Permission::UsersReference))->name('users.search');
        });

    Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();
    Illuminate\Support\Facades\Route::getRoutes()->refreshActionLookups();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function adminApiCustomer(array $attributes = []): User
{
    $customer = User::factory()->create($attributes);
    $customer->assignRole(RoleEnum::Customer);

    return $customer;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function adminApiAddressPayload(array $overrides = []): array
{
    return [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => Country::US->name,
        'phone' => '+14155552671',
        ...$overrides,
    ];
}

test('the customer index is paginated and filterable', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersView]));

    adminApiCustomer(['name' => 'Alice Adams', 'email' => 'alice@example.com']);
    adminApiCustomer(['name' => 'Bruno Baker', 'email' => 'bruno@example.com']);

    getJson('/api/v1/admin/customers?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure([
            'data' => [['id', 'name', 'email', 'order_count', 'lifetime_value', 'last_login_at', 'created_at']],
        ]);

    getJson('/api/v1/admin/customers?query=bruno')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Bruno Baker');
});

test('the customer index only lists customers', function (): void {
    $staff = actingAsApiAdmin(userWithPermissions([Permission::CustomersView]));

    adminApiCustomer(['name' => 'Alice Adams']);

    $response = getJson('/api/v1/admin/customers')->assertOk();

    expect($response->json('data.*.id'))->not->toContain($staff->id);
});

test('a staff member without the view permission cannot list customers', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson('/api/v1/admin/customers')->assertForbidden();
});

test('a customer is shown with addresses', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersView]));

    $customer = adminApiCustomer(['name' => 'Alice Adams']);
    CustomerAddress::factory()->for($customer)->create(['is_default' => true]);

    getJson('/api/v1/admin/customers/' . $customer->id)
        ->assertOk()
        ->assertJsonPath('name', 'Alice Adams')
        ->assertJsonPath('order_count', 0)
        ->assertJsonCount(1, 'addresses')
        ->assertJsonPath('addresses.0.is_default', true);
});

test('a customer is created', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    postJson('/api/v1/admin/customers', [
        'name' => 'Carol Carter',
        'email' => 'carol@example.com',
        'password' => 'Str0ng-Password!',
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'Carol Carter')
        ->assertJsonPath('email', 'carol@example.com')
        ->assertJsonPath('order_count', 0);

    assertDatabaseHas('users', ['email' => 'carol@example.com']);

    expect(User::query()->where('email', 'carol@example.com')->firstOrFail()->hasRole(RoleEnum::Customer))->toBeTrue();
});

test('creating a customer validates the payload', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    postJson('/api/v1/admin/customers', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('a customer is updated with patch semantics', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer(['name' => 'Old Name', 'email' => 'old@example.com']);

    patchJson('/api/v1/admin/customers/' . $customer->id, ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('name', 'New Name')
        ->assertJsonPath('email', 'old@example.com');

    expect($customer->refresh()->name)->toBe('New Name');
});

test('updating a customer rejects an email taken by another user', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer(['email' => 'first@example.com']);
    adminApiCustomer(['email' => 'second@example.com']);

    patchJson('/api/v1/admin/customers/' . $customer->id, ['email' => 'second@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('a customer is deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersDelete]));

    $customer = adminApiCustomer();

    deleteJson('/api/v1/admin/customers/' . $customer->id)->assertOk();

    assertDatabaseMissing('users', ['id' => $customer->id]);
});

test('deleting a user who is not a customer is not found', function (): void {
    $staff = User::factory()->create();

    actingAsApiAdmin(userWithPermissions([Permission::CustomersDelete]));

    deleteJson('/api/v1/admin/customers/' . $staff->id)->assertNotFound();

    assertDatabaseHas('users', ['id' => $staff->id]);
});

test('customers are bulk deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersDelete]));

    $customers = [adminApiCustomer(), adminApiCustomer()];

    deleteJson('/api/v1/admin/customers/bulk', ['ids' => array_map(fn (User $user): int => $user->id, $customers)])
        ->assertNoContent();

    foreach ($customers as $customer) {
        assertDatabaseMissing('users', ['id' => $customer->id]);
    }
});

test('bulk delete rejects unknown customer ids', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersDelete]));

    deleteJson('/api/v1/admin/customers/bulk', ['ids' => [999999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids');
});

test('customer addresses are listed', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersView]));

    $customer = adminApiCustomer();
    CustomerAddress::factory()->for($customer)->create();

    getJson('/api/v1/admin/customers/' . $customer->id . '/addresses')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonStructure([['id', 'first_name', 'last_name', 'country_code', 'is_default']]);
});

test('a customer address is created', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer();

    postJson('/api/v1/admin/customers/' . $customer->id . '/addresses', adminApiAddressPayload(['is_default' => true]))
        ->assertCreated()
        ->assertJsonPath('first_name', 'John')
        ->assertJsonPath('is_default', true);

    assertDatabaseHas('customer_addresses', [
        'user_id' => $customer->id,
        'address_line_1' => '123 Main St',
        'is_default' => true,
    ]);
});

test('creating a customer address validates the payload', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer();

    postJson('/api/v1/admin/customers/' . $customer->id . '/addresses', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name', 'address_line_1', 'country_code']);
});

test('a customer address is updated with patch semantics', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer();
    $address = CustomerAddress::factory()->for($customer)->create(adminApiAddressPayload());

    patchJson(
        '/api/v1/admin/customers/' . $customer->id . '/addresses/' . $address->id,
        ['first_name' => 'Jane'],
    )
        ->assertOk()
        ->assertJsonPath('first_name', 'Jane')
        ->assertJsonPath('last_name', 'Doe');

    expect($address->refresh()->first_name)->toBe('Jane');
});

test('a customer address belonging to another customer cannot be updated', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer();
    $address = CustomerAddress::factory()->for(adminApiCustomer())->create();

    patchJson(
        '/api/v1/admin/customers/' . $customer->id . '/addresses/' . $address->id,
        ['first_name' => 'Jane'],
    )->assertNotFound();
});

test('a customer address is deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer();
    $address = CustomerAddress::factory()->for($customer)->create();

    deleteJson('/api/v1/admin/customers/' . $customer->id . '/addresses/' . $address->id)->assertOk();

    assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
});

test('a customer address is made the default', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CustomersManage]));

    $customer = adminApiCustomer();
    $current = CustomerAddress::factory()->for($customer)->create(['is_default' => true]);
    $next = CustomerAddress::factory()->for($customer)->create(['is_default' => false]);

    postJson('/api/v1/admin/customers/' . $customer->id . '/addresses/' . $next->id . '/default')
        ->assertOk()
        ->assertJsonPath('is_default', true);

    expect($current->refresh()->is_default)->toBeFalse()
        ->and($next->refresh()->is_default)->toBeTrue();
});

test('users are searched for reference pickers', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::UsersReference]));

    adminApiCustomer(['name' => 'Alice Adams', 'email' => 'alice@example.com']);
    adminApiCustomer(['name' => 'Bruno Baker', 'email' => 'bruno@example.com']);

    getJson('/api/v1/admin/users/search?query=alice')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alice Adams')
        ->assertJsonStructure(['data' => [['id', 'name', 'email']]]);
});

test('user search rejects an invalid direction', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::UsersReference]));

    getJson('/api/v1/admin/users/search?direction=sideways')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('direction');
});

test('a staff account is not reachable through the customer endpoints', function (): void {
    $staff = userWithPermissions([Permission::CustomersView]);

    actingAsApiAdmin(userWithPermissions([Permission::CustomersView, Permission::CustomersManage, Permission::CustomersDelete]));

    getJson('/api/v1/admin/customers/' . $staff->id)->assertNotFound();
    patchJson('/api/v1/admin/customers/' . $staff->id, ['name' => 'Renamed'])->assertNotFound();
    deleteJson('/api/v1/admin/customers/' . $staff->id)->assertNotFound();

    expect($staff->refresh()->name)->not->toBe('Renamed');
});

test('a super admin account is not reachable through the customer endpoints', function (): void {
    $owner = User::factory()->create(['email' => 'owner@your-store.com'])
        ->assignRole(Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]));

    actingAsApiAdmin(userWithPermissions([
        Permission::CustomersView,
        Permission::CustomersManage,
        Permission::CustomersDelete,
    ]));

    getJson('/api/v1/admin/customers/' . $owner->id)->assertNotFound();
    patchJson('/api/v1/admin/customers/' . $owner->id, ['email' => 'attacker@example.com'])->assertNotFound();
    deleteJson('/api/v1/admin/customers/' . $owner->id)->assertNotFound();

    expect($owner->refresh()->email)->toBe('owner@your-store.com');
});
