<?php

declare(strict_types=1);

use App\Actions\BulkDestroyCustomerAction;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

covers(BulkDestroyCustomerAction::class);

uses()->group('actions', 'customer');

test('deletes multiple customers by their ids', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customers = User::factory()->count(5)->create();
    foreach ($customers as $customer) {
        $customer->assignRole($customerRole);
    }

    $customerIds = $customers->pluck('id')->all();

    $action = new BulkDestroyCustomerAction();
    $result = $action->handle($customerIds);

    expect($result)->toBe(5)
        ->and(User::query()->whereIn('id', $customerIds)->count())->toBe(0);

    foreach ($customerIds as $id) {
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

    $action = new BulkDestroyCustomerAction();
    $result = $action->handle($ids);

    expect($result)->toBe(2)
        ->and(User::query()->find($customer1->id))->toBeNull()
        ->and(User::query()->find($customer2->id))->toBeNull()
        ->and(User::query()->find($adminUser->id))->not->toBeNull()
        ->and(User::query()->find($regularUser->id))->not->toBeNull();

    assertDatabaseMissing('users', ['id' => $customer1->id]);
    assertDatabaseMissing('users', ['id' => $customer2->id]);
    assertDatabaseHas('users', ['id' => $adminUser->id]);
    assertDatabaseHas('users', ['id' => $regularUser->id]);
});

test('returns zero when no customers found for deletion', function () {
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);
    $adminUser = User::factory()->create();
    $adminUser->assignRole($adminRole);

    $regularUser = User::factory()->create();

    $ids = [$adminUser->id, $regularUser->id];

    $action = new BulkDestroyCustomerAction();
    $result = $action->handle($ids);

    expect($result)->toBe(0)
        ->and(User::query()->find($adminUser->id))->not->toBeNull()
        ->and(User::query()->find($regularUser->id))->not->toBeNull();
});

test('returns zero when non-existent ids provided', function () {
    $action = new BulkDestroyCustomerAction();
    $result = $action->handle([999, 1000]);

    expect($result)->toBe(0);
});

test('handles empty array gracefully', function () {
    $action = new BulkDestroyCustomerAction();
    $result = $action->handle([]);

    expect($result)->toBe(0);
});

test('deletes only customers when mixed with non-customer users', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $adminRole = Role::firstOrCreate(['name' => 'Admin']);

    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    $adminUser = User::factory()->create();
    $adminUser->assignRole($adminRole);

    $regularUser = User::factory()->create();

    $ids = [$customer->id, $adminUser->id, $regularUser->id, 9999];

    $action = new BulkDestroyCustomerAction();
    $result = $action->handle($ids);

    expect($result)->toBe(1)
        ->and(User::query()->find($customer->id))->toBeNull()
        ->and(User::query()->find($adminUser->id))->not->toBeNull()
        ->and(User::query()->find($regularUser->id))->not->toBeNull();

    assertDatabaseMissing('users', ['id' => $customer->id]);
    assertDatabaseHas('users', ['id' => $adminUser->id]);
    assertDatabaseHas('users', ['id' => $regularUser->id]);
});

test('handles single customer deletion', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    $action = new BulkDestroyCustomerAction();
    $result = $action->handle([$customer->id]);

    expect($result)->toBe(1);
    assertDatabaseMissing('users', ['id' => $customer->id]);
});
