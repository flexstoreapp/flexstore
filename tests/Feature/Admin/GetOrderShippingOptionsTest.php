<?php

declare(strict_types=1);

use App\Enums\WeightUnit;
use App\Http\Controllers\Admin\OrderShippingOptionController;
use App\Http\Requests\Admin\OrderShippingOptionsRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;

use function Pest\Laravel\post;

covers(OrderShippingOptionController::class, OrderShippingOptionsRequest::class);

uses()->group('order', 'shipping');

test('returns eligible shipping options for order items', function () {
    $product = Product::factory()->active()->create(['price' => '100.0000']);
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);

    $shippingRate1 = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '10.0000']);
    $shippingRate2 = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '15.0000']);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['id' => $shippingRate1->id])
        ->assertJsonFragment(['id' => $shippingRate2->id]);
});

test('uses shipping address for shipping options', function () {
    $product = Product::factory()->active()->create(['price' => '100.0000']);
    $carrier = ShippingCarrier::factory()->active()->create();
    $usRegion = Region::factory()->create(['countries' => ['US']]);
    $caRegion = Region::factory()->create(['countries' => ['CA']]);

    $usShippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($usRegion)->active()->create();
    ShippingRate::factory()->for($carrier, 'carrier')->for($caRegion)->active()->create();

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $usShippingRate->id]);
});

test('uses shipping address country for shipping options', function () {
    $product = Product::factory()->active()->create(['price' => '100.0000']);
    $carrier = ShippingCarrier::factory()->active()->create();
    $usRegion = Region::factory()->create(['countries' => ['US']]);
    $caRegion = Region::factory()->create(['countries' => ['CA']]);

    ShippingRate::factory()->for($carrier, 'carrier')->for($usRegion)->active()->create();
    $caShippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($caRegion)->active()->create();

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'CA',
            'state' => 'ON',
            'postal_code' => 'M5H 2N2',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $caShippingRate->id]);
});

test('filters shipping options based on order value constraints', function () {
    $product = Product::factory()->active()->create(['price' => '50.0000']);
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);

    $eligibleRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_order_value' => '40.0000',
        'max_order_value' => '100.0000',
    ]);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_order_value' => '100.0000',
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $eligibleRate->id]);
});

test('filters shipping options based on weight constraints', function () {
    $product = Product::factory()->active()->create([
        'price' => '100.0000',
        'weight' => '1.5',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);

    $eligibleRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg,
        'max_weight' => '2.00',
        'max_weight_unit' => WeightUnit::Kg,
    ]);
    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'max_weight' => '1.00',
        'max_weight_unit' => WeightUnit::Kg,
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $eligibleRate->id]);
});

test('excludes shipping options for excluded products', function () {
    $product = Product::factory()->active()->create(['price' => '100.0000']);
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);

    $includedRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_products' => [],
    ]);
    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_products' => [$product->id],
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $includedRate->id]);
});

test('excludes shipping options for excluded categories', function () {
    $category = Category::factory()->active()->create();
    $product = Product::factory()->active()->create(['price' => '100.0000', 'category_id' => $category->id]);
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);

    $includedRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_categories' => [],
    ]);
    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_categories' => [$category->id],
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $includedRate->id]);
});

test('excludes shipping options for excluded brands', function () {
    $brand = Brand::factory()->active()->create();
    $product = Product::factory()->active()->create(['price' => '100.0000', 'brand_id' => $brand->id]);
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);

    $includedRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_brands' => [],
    ]);
    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_brands' => [$brand->id],
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['id' => $includedRate->id]);
});

test('returns correct shipping option structure', function () {
    $product = Product::factory()->active()->create(['price' => '100.0000']);
    $carrier = ShippingCarrier::factory()->active()->create(['name' => 'Test Carrier']);
    $region = Region::factory()->active()->create(['countries' => []]);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'name' => 'Standard Shipping',
        'rate' => '10.0000',
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), $data);

    $response->assertOk()
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'carrier_name',
                'type',
                'rate',
                'delivery_time',
            ],
        ])
        ->assertJsonFragment([
            'id' => $shippingRate->id,
            'rate' => '10.0000',
        ]);
});

test('validates required items field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertInvalid('items');
});

test('validates items is not empty', function () {
    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'items' => [],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertInvalid('items');
});

test('validates items product_id exists', function () {
    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'items' => [
            [
                'product_id' => 99999,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertInvalid('items.0.product_id');
});

test('validates items quantity is positive', function () {
    $product = Product::factory()->active()->create();

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 0,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertInvalid('items.0.quantity');
});

test('validates required shipping address fields', function () {
    $product = Product::factory()->active()->create();

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [],
    ]);

    $response->assertInvalid([
        'shipping_address.country_code',
        'shipping_address.postal_code',
    ]);
});

test('validates shipping address country code format', function () {
    $product = Product::factory()->active()->create();

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'USA',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertInvalid('shipping_address.country_code');
});

test('validates billing address when different_billing_address is true', function () {
    $product = Product::factory()->active()->create();

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
        'different_billing_address' => true,
        'billing_address' => null,
    ]);

    $response->assertInvalid('billing_address');
});

test('validates billing address fields when provided', function () {
    $product = Product::factory()->active()->create();

    $response = actingAsSuperAdmin()->post(route('admin.orders.shipping-options'), [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
        'different_billing_address' => true,
        'billing_address' => [
            'country_code' => 'INVALID',
            'state' => '',
            'postal_code' => '',
        ],
    ]);

    $response->assertInvalid([
        'billing_address.country_code',
        'billing_address.postal_code',
    ]);
});

test('requires authentication', function () {
    $product = Product::factory()->active()->create();

    $response = post(route('admin.orders.shipping-options'), [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertRedirect(route('admin.login'));
});
