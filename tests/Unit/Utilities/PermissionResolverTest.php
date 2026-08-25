<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Utilities\PermissionResolver;

covers(PermissionResolver::class);

uses()->group('permissions');

test('a permission grants the reference tier it implies', function () {
    $user = userWithPermissions([Permission::SettingsTaxConfigure]);

    $abilities = PermissionResolver::effectiveAbilities($user);

    expect($abilities)->toContain(Permission::SettingsTaxConfigure->value)
        ->toContain(Permission::RegionsReference->value)
        ->not->toContain(Permission::RegionsView->value);
});

test('reference grants do not leak section access', function () {
    $user = userWithPermissions([Permission::ProductsManage]);

    $abilities = PermissionResolver::effectiveAbilities($user);

    expect($abilities)
        ->toContain(Permission::CategoriesReference->value)
        ->toContain(Permission::BrandsReference->value)
        ->toContain(Permission::MediaUpload->value)
        ->not->toContain(Permission::CategoriesView->value)
        ->not->toContain(Permission::BrandsView->value);
});

test('a viewer can reference its own resource', function () {
    $user = userWithPermissions([Permission::RegionsView]);

    expect(PermissionResolver::effectiveAbilities($user))
        ->toContain(Permission::RegionsReference->value);
});

test('permissions without implications resolve to themselves only', function () {
    $user = userWithPermissions([Permission::DashboardView]);

    expect(PermissionResolver::effectiveAbilities($user)->all())
        ->toBe([Permission::DashboardView->value]);
});

test('grantedBy lists every permission that implies a reference ability', function () {
    $sources = array_map(
        fn (Permission $permission): string => $permission->value,
        PermissionResolver::grantedBy(Permission::RegionsReference->value),
    );

    expect($sources)->toEqualCanonicalizing([
        Permission::RegionsView->value,
        Permission::SettingsTaxConfigure->value,
        Permission::SettingsShippingConfigure->value,
        Permission::SettingsPaymentConfigure->value,
    ])->not->toContain(Permission::RegionsReference->value);
});

test('grantedBy is empty for an ability nothing implies', function () {
    expect(PermissionResolver::grantedBy(Permission::DashboardView->value))->toBe([]);
});
