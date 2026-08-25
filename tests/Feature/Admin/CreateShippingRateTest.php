<?php

declare(strict_types=1);

use App\Actions\StoreShippingRateAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Requests\Admin\StoreShippingRateRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\castAsJson;
use function Pest\Laravel\post;

covers(ShippingRateController::class, StoreShippingRateRequest::class, StoreShippingRateAction::class);

uses()->group('shipping');

test('creates a shipping rate', function () {
    $region = Region::factory()->create(['name' => 'Test Region']);
    $carrier = ShippingCarrier::factory()->create(['name' => 'Test Carrier']);

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Express Shipping',
        'type' => ShippingRateType::Flat->value,
        'rate' => '19.9900',
        'min_order_value' => '199.9900',
        'max_order_value' => '999.9900',
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '10.00',
        'max_weight_unit' => WeightUnit::Kg->value,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => castAsTranslatableJson('Express Shipping'),
        'type' => ShippingRateType::Flat->value,
        'rate' => '19.9900',
        'min_order_value' => '199.9900',
        'max_order_value' => '999.9900',
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '10.00',
        'max_weight_unit' => WeightUnit::Kg->value,
        'is_active' => true,
    ]);
});

test('validates required fields', function () {

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => '',
        'shipping_carrier_id' => '',
        'name' => '',
        'type' => '',
        'rate' => '',
        'is_active' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'region_id',
            'shipping_carrier_id',
            'name',
            'type',
            'is_active',
        ]);
});

test('rate is required when type is flat', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Flat',
        'type' => 'flat',
        'rate' => '',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()->assertInvalid('rate');
});

test('calculated rate is rejected when carrier does not support live rates', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->manual()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Live Rates',
        'type' => 'live',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()->assertInvalid('type');
});

test('validates weight unit is required when weight is provided', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'min_weight' => '1.00',
        'max_weight' => '5.00',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['min_weight_unit', 'max_weight_unit']);
});

test('validates weight unit values', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'min_weight' => '1.00',
        'min_weight_unit' => 'invalid_unit',
        'max_weight' => '5.00',
        'max_weight_unit' => 'another_invalid',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['min_weight_unit', 'max_weight_unit']);
});

test('creates a free shipping rate', function () {
    $region = Region::factory()->create(['name' => 'Test Region']);
    $carrier = ShippingCarrier::factory()->create(['name' => 'Test Carrier']);

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Free Shipping',
        'type' => ShippingRateType::Free->value,
        'rate' => '9.9900',
        'min_order_value' => '100',
        'max_order_value' => null,
        'min_weight' => null,
        'max_weight' => null,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => castAsTranslatableJson('Free Shipping'),
        'type' => ShippingRateType::Free->value,
        'rate' => null, // rate is null for free shipping
        'min_order_value' => '100',
    ]);
});

test('validates max_order_value is greater than min_order_value', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'min_order_value' => '100',
        'max_order_value' => '50',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('max_order_value');
});

test('validates max_weight is greater than min_weight', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'min_weight' => '10.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '5.00',
        'max_weight_unit' => WeightUnit::Kg->value,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('max_weight');
});

test('validates excluded_products array contains valid product IDs', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'excluded_products' => [999999],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_products.0');
});

test('validates excluded_categories array contains valid category IDs', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '19.9900',
        'excluded_categories' => [999999],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_categories.0');
});

test('creates shipping rate with valid excluded products', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $product = Product::factory()->create();

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'excluded_products' => [$product->id],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'name' => castAsTranslatableJson('Test Rate'),
        'excluded_products' => castAsJson([$product->id]),
    ]);
});

test('creates shipping rate with valid excluded categories', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $category = Category::factory()->create();

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'excluded_categories' => [$category->id],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'name' => castAsTranslatableJson('Test Rate'),
        'excluded_categories' => castAsJson([$category->id]),
    ]);
});

test('validates excluded_brands array contains valid brand IDs', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '19.9900',
        'excluded_brands' => [999999],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_brands.0');
});

test('creates shipping rate with valid excluded brands', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();
    $brand = Brand::factory()->create();

    $data = [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'excluded_brands' => [$brand->id],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'name' => castAsTranslatableJson('Test Rate'),
        'excluded_brands' => castAsJson([$brand->id]),
    ]);
});

test('validates delivery_time max length', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'delivery_time' => str_repeat('a', 256),
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('delivery_time');
});

test('validates name max length', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => str_repeat('a', 256),
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates negative values are not allowed for numeric fields', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '-10.99',
        'min_order_value' => -50,
        'max_order_value' => -100,
        'min_weight' => -1,
        'max_weight' => -5,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'rate',
            'min_order_value',
            'max_order_value',
            'min_weight',
            'max_weight',
        ]);
});

test('validates invalid shipping rate type', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => 'invalid_type',
        'rate' => '10.9900',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('type');
});

test('validates non-existent region and carrier IDs', function () {
    $response = actingAsSuperAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => 999999,
        'shipping_carrier_id' => 999999,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['region_id', 'shipping_carrier_id']);
});

test('requires authentication', function () {
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires shipping.rates.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $region = Region::factory()->create();
    $carrier = ShippingCarrier::factory()->create();

    $response = actingAsAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Test Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'name' => castAsTranslatableJson('Test Rate'),
    ]);

    $role->revokePermissionTo(Permission::SettingsShippingConfigure);

    $response = actingAsAdmin()->post(route('admin.shipping.rates.store'), [
        'region_id' => $region->id,
        'shipping_carrier_id' => $carrier->id,
        'name' => 'Another Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '15.9900',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('shipping_rates', [
        'name' => castAsTranslatableJson('Another Rate'),
    ]);
});
