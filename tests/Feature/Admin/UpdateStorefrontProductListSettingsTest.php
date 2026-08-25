<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\ListLoadingMethod;
use App\Enums\Permission;
use App\Enums\ProductSortOption;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontProductListController;
use App\Http\Requests\Admin\UpdateStorefrontProductListSettingsRequest;
use App\Models\Setting;
use App\Queries\ProductListSettingsQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers([
    StorefrontProductListController::class,
    UpdateStorefrontProductListSettingsRequest::class,
    UpdateSettingsAction::class,
    ProductListSettingsQuery::class,
]);

uses()->group('storefront');

test('displays product list settings page with current settings', function () {
    Setting::setValue('storefront_product_list_loading_method', ListLoadingMethod::InfiniteScroll->value);
    Setting::setValue('storefront_product_list_default_per_page', 36);
    Setting::setValue('storefront_product_list_default_sort', ProductSortOption::PriceLowHigh->value);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.product-list.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/product-list')
                ->where('settings.loading_method', ListLoadingMethod::InfiniteScroll->value)
                ->where('settings.default_per_page', 36)
                ->where('settings.default_sort', ProductSortOption::PriceLowHigh->value)
        );
});

test('displays default settings when no settings exist', function () {
    Setting::query()->where('key', 'like', 'storefront_product_list_%')->delete();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.product-list.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/product-list')
                ->where('settings.loading_method', ListLoadingMethod::Pagination->value)
                ->where('settings.default_per_page', 24)
                ->where('settings.default_sort', ProductSortOption::Latest->value)
        );
});

test('updates loading method setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_loading_method' => ListLoadingMethod::LoadMore->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_list_loading_method',
        'value' => ListLoadingMethod::LoadMore->value,
    ]);
});

test('updates default per page setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_default_per_page' => 48,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_list_default_per_page',
        'value' => '48',
    ]);
});

test('updates default sort setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_default_sort' => ProductSortOption::NameAZ->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_list_default_sort',
        'value' => ProductSortOption::NameAZ->value,
    ]);
});

test('updates multiple settings at once', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_loading_method' => ListLoadingMethod::InfiniteScroll->value,
        'storefront_product_list_default_per_page' => 36,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_list_loading_method',
        'value' => ListLoadingMethod::InfiniteScroll->value,
    ]);
    assertDatabaseHas('settings', [
        'key' => 'storefront_product_list_default_per_page',
        'value' => '36',
    ]);
});

test('validates loading method enum', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_loading_method' => 'invalid_method',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_list_loading_method');
});

test('validates default per page must be in allowed values', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_default_per_page' => 100,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_list_default_per_page');
});

test('validates default sort enum', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_default_sort' => 'invalid_sort',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_list_default_sort');
});

test('requires authentication for edit', function () {
    $response = get(route('admin.storefront.product-list.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires authentication for update', function () {
    $response = patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_loading_method' => ListLoadingMethod::LoadMore->value,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission for edit', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.product-list.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.product-list.edit'));

    $response->assertForbidden();
});

test('requires storefront.update permission for update', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_loading_method' => ListLoadingMethod::LoadMore->value,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.product-list.update'), [
        'storefront_product_list_loading_method' => ListLoadingMethod::Pagination->value,
    ]);

    $response->assertForbidden();
});
