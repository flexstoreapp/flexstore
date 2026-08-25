<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\PermissionKind;

covers(Permission::class);

uses()->group('permissions');

test('only reference permissions may be implied', function () {
    foreach (Permission::cases() as $permission) {
        foreach ($permission->implies() as $implied) {
            expect($implied->kind())
                ->toBe(PermissionKind::Reference, "{$permission->value} implies non-reference {$implied->value}");
        }
    }
});

test('reference permissions are not assignable', function () {
    $reference = array_filter(
        Permission::cases(),
        fn (Permission $permission): bool => $permission->kind() === PermissionKind::Reference,
    );

    expect($reference)->not->toBeEmpty();

    foreach ($reference as $permission) {
        expect($permission->isAssignable())->toBeFalse();
    }
});

test('non-reference permissions are assignable', function () {
    foreach (Permission::cases() as $permission) {
        if ($permission->kind() !== PermissionKind::Reference) {
            expect($permission->isAssignable())->toBeTrue();
        }
    }
});

test('settings returns every settings-scoped permission', function () {
    $settings = Permission::settings();

    expect($settings)->not->toBeEmpty();

    foreach (Permission::cases() as $permission) {
        $isSettings = str_starts_with($permission->value, 'settings.');

        expect(in_array($permission, $settings, true))->toBe($isSettings, $permission->value);
    }
});

test('kind is derived from the action segment', function () {
    expect(Permission::RegionsView->kind())->toBe(PermissionKind::View)
        ->and(Permission::RegionsReference->kind())->toBe(PermissionKind::Reference)
        ->and(Permission::MediaUpload->kind())->toBe(PermissionKind::Reference)
        ->and(Permission::ProductsDelete->kind())->toBe(PermissionKind::Destructive)
        ->and(Permission::ProductsManage->kind())->toBe(PermissionKind::Manage)
        ->and(Permission::SettingsTaxConfigure->kind())->toBe(PermissionKind::Manage);
});
