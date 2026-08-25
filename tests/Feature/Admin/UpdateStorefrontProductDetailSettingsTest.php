<?php

declare(strict_types=1);

use App\Actions\UpdateSettingsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontProductDetailController;
use App\Http\Requests\Admin\UpdateStorefrontProductDetailRequest;
use App\Models\Setting;
use App\Queries\ProductDetailSettingsQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers([
    StorefrontProductDetailController::class,
    UpdateStorefrontProductDetailRequest::class,
    UpdateSettingsAction::class,
    ProductDetailSettingsQuery::class,
]);

uses()->group('storefront');

test('displays product detail settings page with current settings', function () {
    $items = [
        [
            'icon_name' => 'gift',
            'title' => ['en' => 'Free Gift'],
            'subtitle' => ['en' => 'With every order'],
        ],
    ];

    Setting::setValue('storefront_product_detail_show_info_strip', false);
    Setting::setValue('storefront_product_detail_info_strip', $items);
    Setting::setValue('storefront_product_detail_show_related_products', false);
    Setting::setValue('storefront_product_detail_related_products_count', 8);
    Setting::setValue('storefront_product_detail_show_reviews', false);
    Setting::setValue('storefront_product_detail_reviews_per_page', 15);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.product-detail.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/product-detail')
                ->where('settings.show_info_strip', false)
                ->where('settings.info_strip', $items)
                ->where('settings.show_related_products', false)
                ->where('settings.related_products_count', 8)
                ->where('settings.show_reviews', false)
                ->where('settings.reviews_per_page', 15)
        );
});

test('displays default settings when no settings exist', function () {
    Setting::query()->where('key', 'like', 'storefront_product_detail_%')->delete();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.product-detail.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/product-detail')
                ->where('settings.show_info_strip', true)
                ->where('settings.show_related_products', true)
                ->where('settings.related_products_count', 10)
                ->where('settings.show_reviews', true)
                ->where('settings.reviews_per_page', 10)
        );
});

test('updates show info strip setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_show_info_strip' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_show_info_strip',
        'value' => '0',
    ]);
});

test('updates info strip setting wrapping strings as translatable for current locale', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_info_strip' => [
            ['icon_name' => 'shipping', 'title' => 'Fast Shipping', 'subtitle' => 'Ships in 24 hours'],
            ['icon_name' => 'shield', 'title' => 'Secure', 'subtitle' => 'SSL Protected'],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $locale = app()->getLocale();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_info_strip',
        'value' => json_encode([
            [
                'icon_name' => 'shipping',
                'title' => [$locale => 'Fast Shipping'],
                'subtitle' => [$locale => 'Ships in 24 hours'],
            ],
            [
                'icon_name' => 'shield',
                'title' => [$locale => 'Secure'],
                'subtitle' => [$locale => 'SSL Protected'],
            ],
        ]),
    ]);
});

test('updating info strip preserves translations for other locales', function () {
    Setting::setValue('storefront_product_detail_info_strip', [
        [
            'icon_name' => 'shipping',
            'title' => ['en' => 'Fast Shipping', 'bn' => 'দ্রুত শিপিং'],
            'subtitle' => ['en' => 'Ships in 24 hours', 'bn' => '২৪ ঘণ্টায় ডেলিভারি'],
        ],
    ]);

    $response = actingAsSuperAdmin()
        ->withHeaders(['Accept-Language' => 'en'])
        ->patch(route('admin.storefront.product-detail.update'), [
            'storefront_product_detail_info_strip' => [
                ['icon_name' => 'shipping', 'title' => 'Updated Title', 'subtitle' => 'Updated description'],
            ],
        ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $locale = app()->getLocale();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_info_strip',
        'value' => json_encode([
            [
                'icon_name' => 'shipping',
                'title' => ['en' => 'Fast Shipping', 'bn' => 'দ্রুত শিপিং', $locale => 'Updated Title'],
                'subtitle' => ['en' => 'Ships in 24 hours', 'bn' => '২৪ ঘণ্টায় ডেলিভারি', $locale => 'Updated description'],
            ],
        ]),
    ]);
});

test('updates show related products setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_show_related_products' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_show_related_products',
        'value' => '0',
    ]);
});

test('updates related products count setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_related_products_count' => 20,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_related_products_count',
        'value' => '20',
    ]);
});

test('updates show reviews setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_show_reviews' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_show_reviews',
        'value' => '0',
    ]);
});

test('updates reviews per page setting', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_reviews_per_page' => 20,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_reviews_per_page',
        'value' => '20',
    ]);
});

test('updates multiple settings at once', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_show_info_strip' => false,
        'storefront_product_detail_show_related_products' => false,
        'storefront_product_detail_related_products_count' => 10,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_show_info_strip',
        'value' => '0',
    ]);
    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_show_related_products',
        'value' => '0',
    ]);
    assertDatabaseHas('settings', [
        'key' => 'storefront_product_detail_related_products_count',
        'value' => '10',
    ]);
});

test('validates related products count must be in allowed values', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_related_products_count' => 100,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_detail_related_products_count');
});

test('validates reviews per page must be in allowed values', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_reviews_per_page' => 100,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_detail_reviews_per_page');
});

test('validates info strip must be an array', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_info_strip' => 'not an array',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_detail_info_strip');
});

test('validates info strip cannot exceed 3 items', function () {
    $items = array_fill(0, 4, [
        'icon_name' => 'shipping',
        'title' => 'Feature',
        'subtitle' => 'Description',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_info_strip' => $items,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_detail_info_strip');
});

test('validates info strip items must have required fields', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_info_strip' => [
            ['icon_name' => 'shipping'],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('storefront_product_detail_info_strip.0.title');
});

test('requires authentication for edit', function () {
    $response = get(route('admin.storefront.product-detail.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires authentication for update', function () {
    $response = patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_show_info_strip' => false,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission for edit', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.product-detail.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.product-detail.edit'));

    $response->assertForbidden();
});

test('requires storefront.update permission for update', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_show_info_strip' => false,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->patch(route('admin.storefront.product-detail.update'), [
        'storefront_product_detail_show_info_strip' => true,
    ]);

    $response->assertForbidden();
});
