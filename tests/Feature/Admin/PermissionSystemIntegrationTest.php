<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses()->group('role');

test('Super Admin bypasses all permission checks', function () {
    $superAdminRole = Role::firstOrCreate(['name' => RoleEnum::SuperAdmin, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($superAdminRole);

    // Super Admin should have access to all permissions without explicitly assigning them
    expect($user->can('staff.view'))->toBeTrue()
        ->and($user->can('staff.create'))->toBeTrue()
        ->and($user->can('staff.update'))->toBeTrue()
        ->and($user->can('staff.delete'))->toBeTrue()
        ->and($user->can('roles.view'))->toBeTrue()
        ->and($user->can('roles.create'))->toBeTrue()
        ->and($user->can('roles.update'))->toBeTrue()
        ->and($user->can('roles.delete'))->toBeTrue()
        ->and($user->can('non.existent.permission'))->toBeTrue();
});

test('user with specific role permissions can access allowed resources', function () {
    $role = Role::create(['name' => 'Test Manager', 'guard_name' => 'web']);
    $viewPermission = Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);
    $createPermission = Permission::firstOrCreate(['name' => 'staff.create', 'guard_name' => 'web']);

    $role->givePermissionTo([$viewPermission, $createPermission]);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('staff.view'))->toBeTrue()
        ->and($user->can('staff.create'))->toBeTrue()
        ->and($user->can('staff.update'))->toBeFalse()
        ->and($user->can('staff.delete'))->toBeFalse();
});

test('user without permissions cannot access restricted resources', function () {
    $role = Role::create(['name' => 'Limited User', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('staff.view'))->toBeFalse()
        ->and($user->can('staff.create'))->toBeFalse()
        ->and($user->can('staff.update'))->toBeFalse()
        ->and($user->can('staff.delete'))->toBeFalse()
        ->and($user->can('roles.view'))->toBeFalse()
        ->and($user->can('roles.create'))->toBeFalse();
});

test('user with multiple roles inherits all permissions', function () {
    $userRole = Role::create(['name' => 'User Manager', 'guard_name' => 'web']);
    $productRole = Role::create(['name' => 'Product Manager', 'guard_name' => 'web']);

    $userViewPermission = Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);
    $userCreatePermission = Permission::firstOrCreate(['name' => 'staff.create', 'guard_name' => 'web']);
    $productViewPermission = Permission::firstOrCreate(['name' => 'products.view', 'guard_name' => 'web']);
    $productUpdatePermission = Permission::firstOrCreate(['name' => 'products.update', 'guard_name' => 'web']);

    $userRole->givePermissionTo([$userViewPermission, $userCreatePermission]);
    $productRole->givePermissionTo([$productViewPermission, $productUpdatePermission]);

    $user = User::factory()->create();
    $user->assignRole([$userRole, $productRole]);

    expect($user->can('staff.view'))->toBeTrue()
        ->and($user->can('staff.create'))->toBeTrue()
        ->and($user->can('products.view'))->toBeTrue()
        ->and($user->can('products.update'))->toBeTrue()
        ->and($user->can('staff.delete'))->toBeFalse()
        ->and($user->can('products.delete'))->toBeFalse();
});

test('user with direct permissions bypasses role permissions', function () {
    $role = Role::create(['name' => 'Basic User', 'guard_name' => 'web']);
    $rolePermission = Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);
    $directPermission = Permission::firstOrCreate(['name' => 'staff.create', 'guard_name' => 'web']);

    $role->givePermissionTo($rolePermission);

    $user = User::factory()->create();
    $user->assignRole($role);
    $user->givePermissionTo($directPermission);

    expect($user->can('staff.view'))->toBeTrue() // From role
        ->and($user->can('staff.create'))->toBeTrue() // Direct permission
        ->and($user->can('staff.update'))->toBeFalse(); // Neither
});

test('removing role removes associated permissions', function () {
    $role = Role::create(['name' => 'Temporary Role', 'guard_name' => 'web']);
    $permission = Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);

    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->can('staff.view'))->toBeTrue();

    $user->removeRole($role);

    expect($user->can('staff.view'))->toBeFalse();
});

test('removing permission from role affects all users with that role', function () {
    $role = Role::create(['name' => 'Shared Role', 'guard_name' => 'web']);
    $permission = Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);

    $role->givePermissionTo($permission);

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->assignRole($role);
    $user2->assignRole($role);

    expect($user1->can('staff.view'))->toBeTrue()
        ->and($user2->can('staff.view'))->toBeTrue();

    $role->revokePermissionTo($permission);

    // Refresh users to get updated permissions
    $user1->refresh();
    $user2->refresh();

    expect($user1->can('staff.view'))->toBeFalse()
        ->and($user2->can('staff.view'))->toBeFalse();
});

test('permission checking works with different guard names', function () {
    $webRole = Role::create(['name' => 'Web User', 'guard_name' => 'web']);
    $apiRole = Role::create(['name' => 'API User', 'guard_name' => 'api']);

    $webPermission = Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'web']);
    $apiPermission = Permission::firstOrCreate(['name' => 'staff.view', 'guard_name' => 'api']);

    $webRole->givePermissionTo($webPermission);
    $apiRole->givePermissionTo($apiPermission);

    $webUser = User::factory()->create();
    $webUser->assignRole($webRole);

    expect($webUser->can('staff.view'))->toBeTrue() // Web guard permission
        ->and($webUser->can('staff.view', 'api'))->toBeFalse(); // API guard permission
});
