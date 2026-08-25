<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Admin\RegionSearchController;

use function Pest\Laravel\actingAs;

covers(RegionSearchController::class);

uses()->group('permissions');

test('managing tax rates grants region lookup without region section access', function () {
    $user = userWithPermissions([Permission::SettingsTaxConfigure]);

    actingAs($user)->get(route('admin.regions.search'))->assertOk();
    actingAs($user)->get(route('admin.regions.index'))->assertForbidden();
});

test('a user without any region access cannot reach region lookup', function () {
    $user = userWithPermissions([Permission::DashboardView]);

    actingAs($user)->get(route('admin.regions.search'))->assertForbidden();
});

test('managing shipping rates grants product and category lookup', function () {
    $user = userWithPermissions([Permission::SettingsShippingConfigure]);

    actingAs($user)->get(route('admin.products.search'))->assertOk();
    actingAs($user)->get(route('admin.categories.search'))->assertOk();
    actingAs($user)->get(route('admin.regions.search'))->assertOk();
});

test('order helper endpoints require the orders compose ability', function () {
    $editor = userWithPermissions([Permission::OrdersManage]);
    $outsider = userWithPermissions([Permission::ProductsView]);

    actingAs($outsider)->post(route('admin.orders.calculate-taxes'))->assertForbidden();
    expect(actingAs($editor)->post(route('admin.orders.calculate-taxes'))->status())->not->toBe(403);
});

test('media upload requires the media upload ability', function () {
    $editor = userWithPermissions([Permission::ProductsManage]);
    $outsider = userWithPermissions([Permission::OrdersView]);

    actingAs($outsider)->post(route('admin.media.store'))->assertForbidden();
    expect(actingAs($editor)->post(route('admin.media.store'))->status())->not->toBe(403);
});
