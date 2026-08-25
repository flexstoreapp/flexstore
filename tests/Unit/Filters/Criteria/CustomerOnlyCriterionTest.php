<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Enums\Role as RoleEnum;
use App\Filters\Criteria\CustomerOnlyCriterion;
use App\Models\User;
use Spatie\Permission\Models\Role;

covers(CustomerOnlyCriterion::class);

uses()->group('filters');

test('canApply returns true for non-empty values', function () {
    $criterion = new CustomerOnlyCriterion();

    expect($criterion->canApply(true))->toBeTrue()
        ->and($criterion->canApply(false))->toBeTrue()
        ->and($criterion->canApply('true'))->toBeTrue()
        ->and($criterion->canApply('false'))->toBeTrue()
        ->and($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply('0'))->toBeTrue()
        ->and($criterion->canApply(1))->toBeTrue()
        ->and($criterion->canApply(0))->toBeTrue();
});

test('canApply returns false for null or empty values', function () {
    $criterion = new CustomerOnlyCriterion();

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse();
});

test('filters users to only customers when true', function () {
    $customerRole = Role::query()->firstOrCreate(['name' => RoleEnum::Customer]);
    $adminRole = Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);

    $customer1 = User::factory()->create();
    $customer1->assignRole($customerRole);

    $customer2 = User::factory()->create();
    $customer2->assignRole($customerRole);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $criterion = new CustomerOnlyCriterion();
    $builder = User::query();

    $result = $criterion->apply($builder, true);

    expect($result->count())->toBe(2);

    $result->each(function ($user) use ($customer1, $customer2) {
        expect($user->id)->toBeIn([$customer1->id, $customer2->id]);
    });
});

test('does not filter when false', function () {
    $customerRole = Role::query()->firstOrCreate(['name' => RoleEnum::Customer]);
    $adminRole = Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);

    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $criterion = new CustomerOnlyCriterion();
    $builder = User::query();

    $result = $criterion->apply($builder, false);

    expect($result->count())->toBeGreaterThanOrEqual(2);
});

test('converts string true values correctly', function () {
    $customerRole = Role::query()->firstOrCreate(['name' => RoleEnum::Customer]);
    $adminRole = Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);

    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $criterion = new CustomerOnlyCriterion();

    $trueValues = ['true', '1', 'yes', 'on'];

    foreach ($trueValues as $value) {
        $result = $criterion->apply(User::query(), $value);
        expect($result->count())->toBe(1);
        expect($result->first()->id)->toBe($customer->id);
    }
});

test('converts string false values correctly', function () {
    $customerRole = Role::query()->firstOrCreate(['name' => RoleEnum::Customer]);
    $adminRole = Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]);

    $customer = User::factory()->create();
    $customer->assignRole($customerRole);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $criterion = new CustomerOnlyCriterion();

    $falseValues = ['false', '0', 'no', 'off', 'anything_else'];

    foreach ($falseValues as $value) {
        $result = $criterion->apply(User::query(), $value);
        expect($result->count())->toBeGreaterThanOrEqual(2);
    }
});
