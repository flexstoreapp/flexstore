<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Models\Order;
use App\Models\User;
use App\Queries\CustomerListQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

covers(CustomerListQuery::class);

uses()->group('queries', 'customer');

test('returns paginated customers', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    User::factory()->count(3)->create()->each(fn (User $user) => $user->assignRole($customerRole));

    $query = app(CustomerListQuery::class);
    $result = $query->execute();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(3);
});

test('returns empty paginator when no customers exist', function () {
    $query = app(CustomerListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('only returns users with customer role', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $editorRole = Role::firstOrCreate(['name' => 'editor']);

    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    $editor = User::factory()->create();
    $editor->assignRole($editorRole);

    $query = app(CustomerListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($customer->id);
});

test('includes order count', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    Order::factory()->count(3)->create(['customer_id' => $customer->id]);

    $query = app(CustomerListQuery::class);
    $result = $query->execute();

    expect($result->first()->order_count)->toBe(3);
});

test('respects per page parameter', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    User::factory()->count(10)->create()->each(fn (User $user) => $user->assignRole($customerRole));

    $query = app(CustomerListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});
