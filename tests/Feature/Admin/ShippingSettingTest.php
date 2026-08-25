<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ShippingSettingController;
use App\Http\Requests\Admin\IndexShippingRateRequest;
use App\Http\Requests\Admin\UpdateShippingSettingRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Queries\ShippingCarrierListQuery;
use App\Queries\ShippingRateListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(ShippingSettingController::class, ShippingCarrierListQuery::class, ShippingRateListQuery::class, IndexShippingRateRequest::class, UpdateShippingSettingRequest::class);

uses()->group('setting', 'shipping');

test('displays shipping settings page', function () {
    $response = actingAsSuperAdmin()->get(route('admin.settings.shipping.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/shipping')
                ->has('shippingCarriers')
                ->has('shippingRates.data')
        );
});

test('displays shipping settings with no shipping carriers', function () {
    $response = actingAsSuperAdmin()->get(route('admin.settings.shipping.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/shipping')
                ->where('shippingCarriers', [])
                ->where('shippingRates.data', [])
                ->where('shippingRates.total', 0)
        );
});

test('displays shipping rates with excluded products', function () {
    $product = Product::factory()->create();
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'excluded_products' => [$product->id],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.shipping.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/shipping')
                ->has('shippingRates.data', 1)
                ->has('shippingRates.data.0.excluded_products', 1)
                ->where('shippingRates.data.0.excluded_products.0.id', $product->id)
                ->where('shippingRates.data.0.excluded_products.0.title.' . app()->getLocale(), $product->title)
        );
});

test('displays shipping rates with excluded categories', function () {
    $category = Category::factory()->create();
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'excluded_categories' => [$category->id],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.shipping.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/shipping')
                ->has('shippingRates.data', 1)
                ->has('shippingRates.data.0.excluded_categories', 1)
                ->where('shippingRates.data.0.excluded_categories.0.id', $category->id)
                ->where('shippingRates.data.0.excluded_categories.0.name.' . app()->getLocale(), $category->name)
        );
});

test('displays shipping rates with excluded brands', function () {
    $brand = Brand::factory()->create();
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    ShippingRate::factory()->create([
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'excluded_brands' => [$brand->id],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.shipping.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/shipping')
                ->has('shippingRates.data', 1)
                ->has('shippingRates.data.0.excluded_brands', 1)
                ->where('shippingRates.data.0.excluded_brands.0.id', $brand->id)
                ->where('shippingRates.data.0.excluded_brands.0.name.' . app()->getLocale(), $brand->name)
        );
});

test('requires authentication', function () {
    $response = get(route('admin.settings.shipping.show'));

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.shipping.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.shipping.show'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::SettingsShippingConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.shipping.show'));

    $response->assertForbidden();
});
