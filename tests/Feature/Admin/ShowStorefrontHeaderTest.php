<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontHeaderController;
use App\Models\Category;
use App\Models\Setting;
use App\Queries\AdminHeaderBrowseCategoriesQuery;
use App\Queries\AdminHeaderMenuItemListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(StorefrontHeaderController::class, AdminHeaderMenuItemListQuery::class, AdminHeaderBrowseCategoriesQuery::class);

uses()->group('storefront');

test('displays header page with settings and configured browse categories', function () {
    $category = Category::factory()->create();
    Setting::setValue('storefront_header_browse_categories', [
        ['category_id' => $category->id, 'is_mega_menu' => true, 'featured_image_id' => null],
    ]);
    Setting::setValue('storefront_header_sticky', true);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.header.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/header')
                ->where('browseCategories.0.category.id', $category->id)
                ->where('browseCategories.0.is_mega_menu', true)
                ->where('settings.sticky', true)
                ->has('menuItems')
        );
});

test('displays empty browse categories when none configured', function () {
    Setting::setValue('storefront_header_browse_categories', []);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.header.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/header')
                ->where('browseCategories', [])
        );
});

test('requires authentication', function () {
    $response = get(route('admin.storefront.header.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.header.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.header.edit'));

    $response->assertForbidden();
});
