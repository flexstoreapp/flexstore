<?php

declare(strict_types=1);

use App\Actions\UpdateShippingRateAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Requests\Admin\UpdateShippingRateRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingRate;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\castAsJson;
use function Pest\Laravel\patch;

covers(ShippingRateController::class, UpdateShippingRateRequest::class, UpdateShippingRateAction::class);

uses()->group('shipping');

test('updates a shipping rate', function () {
    $shippingRate = ShippingRate::factory()->create([
        'name' => 'Old Rate',
        'type' => ShippingRateType::Flat,
        'rate' => '5.9900',
    ]);

    $data = [
        'name' => 'Updated Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '8.9900',
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'name' => castAsTranslatableJson('Updated Rate'),
        'rate' => '8.9900',
    ]);
});

test('validates required fields', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'region_id' => '',
        'shipping_carrier_id' => '',
        'name' => '',
        'type' => '',
        'rate' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'region_id',
            'shipping_carrier_id',
            'name',
        ]);
});

test('rate is required when type is flat', function () {
    $shippingRate = ShippingRate::factory()->flatRate()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'type' => 'flat',
        'rate' => '',
    ]);

    $response->assertRedirectBack()->assertInvalid('rate');
});

test('updates shipping rate type', function () {
    $shippingRate = ShippingRate::factory()->create([
        'name' => 'Flat Rate',
        'type' => ShippingRateType::Flat,
        'rate' => '5.9900',
    ]);

    $data = [
        'name' => 'Free Shipping',
        'type' => ShippingRateType::Free->value,
        'rate' => '0.0000',
        'min_order_value' => '100.0000',
        'max_order_value' => null,
        'min_weight' => null,
        'max_weight' => null,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'name' => castAsTranslatableJson('Free Shipping'),
        'type' => ShippingRateType::Free->value,
        'rate' => null,
        'min_order_value' => '100.0000',
    ]);
});

test('updates shipping rate without changing type preserves existing rate behavior', function () {
    $shippingRate = ShippingRate::factory()->create([
        'name' => 'Free Rate',
        'type' => ShippingRateType::Free,
        'rate' => null,
    ]);

    $data = [
        'name' => 'Updated Free Rate',
        'type' => ShippingRateType::Free->value,
        'rate' => '10.9900',
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'name' => castAsTranslatableJson('Updated Free Rate'),
        'type' => ShippingRateType::Free->value,
        'rate' => null,
    ]);
});

test('requires type when rate is sent', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'rate' => '10.9900',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('type');
});

test('validates weight unit is required when weight is provided', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'min_weight' => '10.00',
        // min_weight_unit is missing
        'max_weight' => '20.00',
        // max_weight_unit is missing
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['min_weight_unit', 'max_weight_unit']);
});

test('validates max_order_value is greater than min_order_value', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'min_order_value' => '100.0000',
        'max_order_value' => '50.0000',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('max_order_value');
});

test('validates max_weight is greater than min_weight', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'min_weight' => '10.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '5.00',
        'max_weight_unit' => WeightUnit::Kg->value,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('max_weight');
});

test('validates excluded_products array contains valid product IDs', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'excluded_products' => [999999],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_products.0');
});

test('validates excluded_categories array contains valid category IDs', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'excluded_categories' => [999999],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_categories.0');
});

test('updates shipping rate with valid excluded products', function () {
    $shippingRate = ShippingRate::factory()->create();
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'excluded_products' => [$product->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'excluded_products' => castAsJson([$product->id]),
    ]);
});

test('updates shipping rate with valid excluded categories', function () {
    $shippingRate = ShippingRate::factory()->create();
    $category = Category::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'excluded_categories' => [$category->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'excluded_categories' => castAsJson([$category->id]),
    ]);
});

test('validates excluded_brands array contains valid brand IDs', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'excluded_brands' => [999999],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_brands.0');
});

test('updates shipping rate with valid excluded brands', function () {
    $shippingRate = ShippingRate::factory()->create();
    $brand = Brand::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'excluded_brands' => [$brand->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'excluded_brands' => castAsJson([$brand->id]),
    ]);
});

test('validates delivery_time max length', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'delivery_time' => str_repeat('a', 256),
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('delivery_time');
});

test('validates name max length', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'name' => str_repeat('a', 256),
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates negative values are not allowed for numeric fields', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'rate' => '-10.99',
        'min_order_value' => '-50.00',
        'max_order_value' => '-100.00',
        'min_weight' => '-1.00',
        'max_weight' => '-5.00',
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
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'type' => 'invalid_type',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('type');
});

test('validates non-existent region and carrier IDs', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'region_id' => 999999,
        'shipping_carrier_id' => 999999,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['region_id', 'shipping_carrier_id']);
});

test('validates weight unit values', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'min_weight' => '10.00',
        'min_weight_unit' => 'invalid_unit',
        'max_weight' => '20.00',
        'max_weight_unit' => 'invalid_unit',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['min_weight_unit', 'max_weight_unit']);
});

test('updates shipping rate with conditions and restrictions', function () {
    $shippingRate = ShippingRate::factory()->create();
    $product = Product::factory()->create();
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();

    $data = [
        'name' => 'Updated Rate with Conditions',
        'min_order_value' => '50',
        'max_order_value' => '500',
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '10.00',
        'max_weight_unit' => WeightUnit::Kg->value,
        'excluded_products' => [$product->id],
        'excluded_categories' => [$category->id],
        'excluded_brands' => [$brand->id],
        'delivery_time' => '3-5 business days',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();
    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'name' => castAsTranslatableJson('Updated Rate with Conditions'),
        'min_order_value' => '50',
        'max_order_value' => '500',
        'min_weight' => '1.00',
        'max_weight' => '10.00',
        'excluded_products' => castAsJson([$product->id]),
        'excluded_categories' => castAsJson([$category->id]),
        'excluded_brands' => castAsJson([$brand->id]),
        'delivery_time' => castAsTranslatableJson('3-5 business days'),
    ]);
});

test('clears conditions and restrictions', function () {
    $shippingRate = ShippingRate::factory()->withConditions()->withRestrictions()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'min_order_value' => null,
        'excluded_products' => [],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'min_order_value' => null,
        'excluded_products' => castAsJson([]),
    ]);
});

test('requires authentication', function () {
    $shippingRate = ShippingRate::factory()->create();

    $response = patch(route('admin.shipping.rates.update', $shippingRate), [
        'name' => 'Updated Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires shipping.rates.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $shippingRate = ShippingRate::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.shipping.rates.update', $shippingRate), [
        'name' => 'Updated Rate',
        'type' => ShippingRateType::Flat->value,
        'rate' => '10.9900',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('shipping_rates', [
        'id' => $shippingRate->id,
        'name' => castAsTranslatableJson('Updated Rate'),
    ]);

    $role->revokePermissionTo(Permission::SettingsShippingConfigure);

    $anotherRate = ShippingRate::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.shipping.rates.update', $anotherRate), [
        'name' => 'Forbidden Update',
        'type' => ShippingRateType::Flat->value,
        'rate' => '15.9900',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('shipping_rates', [
        'name' => castAsTranslatableJson('Forbidden Update'),
    ]);
});
