<?php

declare(strict_types=1);

use App\Enums\PaymentGatewayDriver;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\PaymentSettingController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Queries\PaymentGatewayListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(PaymentSettingController::class, PaymentGatewayListQuery::class);

uses()->group('setting', 'payment');

test('displays payment settings page', function () {
    $response = actingAsSuperAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/payment')
                ->has('paymentGateways')
        );
});

test('makes gateway credentials visible in response', function () {
    PaymentGateway::factory()->create([
        'credentials' => ['api_key' => 'test_key', 'secret' => 'test_secret'],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/payment')
                ->has('paymentGateways', 1)
                ->where('paymentGateways.0.credentials', ['api_key' => 'test_key', 'secret' => 'test_secret'])
        );
});

test('displays payment settings with no payment gateways', function () {
    $response = actingAsSuperAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/payment')
                ->where('paymentGateways', [])
        );
});

test('displays payment gateways with excluded products', function () {
    $product = Product::factory()->create();

    PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Stripe,
        'excluded_products' => [$product->id],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/payment')
                ->has('paymentGateways', 1)
                ->has('paymentGateways.0.excluded_products', 1)
                ->where('paymentGateways.0.excluded_products.0.id', $product->id)
                ->where('paymentGateways.0.excluded_products.0.title.' . app()->getLocale(), $product->title)
        );
});

test('displays payment gateways with excluded categories', function () {
    $category = Category::factory()->create();

    PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Stripe,
        'excluded_categories' => [$category->id],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/payment')
                ->has('paymentGateways', 1)
                ->has('paymentGateways.0.excluded_categories', 1)
                ->where('paymentGateways.0.excluded_categories.0.id', $category->id)
                ->where('paymentGateways.0.excluded_categories.0.name.' . app()->getLocale(), $category->name)
        );
});

test('displays payment gateways with excluded brands', function () {
    $brand = Brand::factory()->create();

    PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Stripe,
        'excluded_brands' => [$brand->id],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/payment')
                ->has('paymentGateways', 1)
                ->has('paymentGateways.0.excluded_brands', 1)
                ->where('paymentGateways.0.excluded_brands.0.id', $brand->id)
                ->where('paymentGateways.0.excluded_brands.0.name.' . app()->getLocale(), $brand->name)
        );
});

test('displays payment gateways with allowed regions', function () {
    $region = Region::factory()->create();

    PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Stripe,
        'allowed_regions' => [$region->id],
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/settings/payment')
                ->has('paymentGateways', 1)
                ->has('paymentGateways.0.allowed_regions', 1)
                ->where('paymentGateways.0.allowed_regions.0.id', $region->id)
                ->where('paymentGateways.0.allowed_regions.0.name.' . app()->getLocale(), $region->name)
        );
});

test('requires authentication', function () {
    $response = get(route('admin.settings.payment.show'));

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.payment.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.settings.payment.show'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::SettingsPaymentConfigure);

    $response = actingAsAdmin()->get(route('admin.settings.payment.show'));

    $response->assertForbidden();
});
