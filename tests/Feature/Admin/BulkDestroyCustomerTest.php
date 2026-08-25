<?php

declare(strict_types=1);

use App\Actions\BulkDestroyCustomerAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BulkCustomerController;
use App\Http\Requests\Admin\BulkDestroyCustomerRequest;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(BulkCustomerController::class, BulkDestroyCustomerRequest::class, BulkDestroyCustomerAction::class);

uses()->group('customer');

test('bulk deletes customers', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customers = User::factory(3)->create();
    foreach ($customers as $customer) {
        $customer->assignRole($customerRole);
    }

    $ids = $customers->pluck('id')->all();

    $response = actingAsSuperAdmin()->delete(route('admin.customers.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    foreach ($ids as $id) {
        assertDatabaseMissing('users', ['id' => $id]);
    }
});

test('only deletes users with customer role', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);

    $customer1 = User::factory()->create();
    $customer1->assignRole($customerRole);

    $customer2 = User::factory()->create();
    $customer2->assignRole($customerRole);

    $adminUser = User::factory()->create();
    $adminUser->assignRole($adminRole);

    $regularUser = User::factory()->create();

    $ids = [$customer1->id, $customer2->id, $adminUser->id, $regularUser->id];

    $response = actingAsSuperAdmin()->delete(route('admin.customers.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('users', ['id' => $customer1->id]);
    assertDatabaseMissing('users', ['id' => $customer2->id]);
    assertDatabaseHas('users', ['id' => $adminUser->id]);
    assertDatabaseHas('users', ['id' => $regularUser->id]);
});

test('requires at least one customer id', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.customers.bulk.destroy'), [
        'ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('validates that ids exist', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.customers.bulk.destroy'), [
        'ids' => [999, 1000],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('requires authentication', function () {
    $response = delete(route('admin.customers.bulk.destroy'), [
        'ids' => [1],
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires customers.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    $response = actingAsAdmin()->delete(route('admin.customers.bulk.destroy'), [
        'ids' => [$customer->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('users', ['id' => $customer->id]);

    $role->revokePermissionTo(Permission::CustomersDelete);

    $customer2 = User::factory()->create();
    $customer2->assignRole($customerRole);

    $response = actingAsAdmin()->delete(route('admin.customers.bulk.destroy'), [
        'ids' => [$customer2->id],
    ]);

    $response->assertForbidden();

    assertDatabaseHas('users', ['id' => $customer2->id]);
});
