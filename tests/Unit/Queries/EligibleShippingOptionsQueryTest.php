<?php

declare(strict_types=1);

use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use App\Enums\WeightUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Queries\EligibleShippingOptionsQuery;

covers(EligibleShippingOptionsQuery::class);

uses()->group('queries', 'checkout');

test('returns eligible shipping rates for items', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $shippingRate1 = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $shippingRate2 = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(2)
        ->and(array_column($result->all(), 'id'))->toContain($shippingRate1->id, $shippingRate2->id);
});

test('filters out inactive shipping rates', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->inactive()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('filters out rates from inactive carriers', function () {
    $inactiveCarrier = ShippingCarrier::factory()->inactive()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    ShippingRate::factory()->for($inactiveCarrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('filters out rates below min order value', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '50.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_order_value' => '100.0000',
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('filters out rates above max order value', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '150.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'max_order_value' => '100.0000',
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('includes rates when order value equals min boundary', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_order_value' => '100.0000',
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('includes rates when order value equals max boundary', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'max_order_value' => '100.0000',
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('filters out rates below min weight', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000', 'weight' => '0.5', 'weight_unit' => WeightUnit::Kg]);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg,
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('filters out rates above max weight', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000', 'weight' => '2.00', 'weight_unit' => WeightUnit::Kg]);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'max_weight' => '1.00',
        'max_weight_unit' => WeightUnit::Kg,
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('includes rates when cart weight is within range', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000', 'weight' => '1.50', 'weight_unit' => WeightUnit::Kg]);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg,
        'max_weight' => '2.00',
        'max_weight_unit' => WeightUnit::Kg,
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('excludes rates with excluded products', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_products' => [$product->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('excludes rates with excluded categories', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $category = Category::factory()->create();
    $product = Product::factory()->create(['price' => '100.0000', 'category_id' => $category->id]);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_categories' => [$category->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('excludes rates with excluded brands', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['price' => '100.0000', 'brand_id' => $brand->id]);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_brands' => [$brand->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('includes rates when product not in exclusion list', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $excludedProduct = Product::factory()->create();
    $cartProduct = Product::factory()->create(['price' => '100.0000']);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'excluded_products' => [$excludedProduct->id],
    ]);

    $items = [['product_id' => $cartProduct->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('filters rates by matching region country', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => ['US']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => 'US']));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('filters out rates when country does not match region', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => ['US']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => 'CA']));

    expect($result)->toHaveCount(0);
});

test('filters out rates when state does not match region', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['states' => ['NY']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['state' => 'CA']));

    expect($result)->toHaveCount(0);
});

test('filters out rates when postal_code does not match region', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['postal_codes' => ['10001']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['postal_code' => '90210']));

    expect($result)->toHaveCount(0);
});

test('includes rates when postal_code matches a wildcard or range pattern', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => [], 'postal_codes' => ['902*', '60601..60699']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);

    expect($query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['postal_code' => '90250'])))->toHaveCount(1);
    expect($query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['postal_code' => '60650'])))->toHaveCount(1);
    expect($query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['postal_code' => '30303'])))->toHaveCount(0);
});

test('excludes region-constrained rates when no address is provided', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => ['US'], 'states' => ['NY']]);
    $product = Product::factory()->create(['price' => '100.0000']);
    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('includes unconstrained region rates when no address is provided', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => [], 'states' => [], 'postal_codes' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('excludes rates when country_code is null but region requires countries', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => ['US']]);
    $product = Product::factory()->create(['price' => '100.0000']);
    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => null]));

    expect($result)->toHaveCount(0);
});

test('includes rates when region has empty country list', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => 'US']));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('returns empty collection when no rates exist', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('calculates subtotal from multiple items', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product1 = Product::factory()->create(['price' => '50.0000']);
    $product2 = Product::factory()->create(['price' => '30.0000']);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_order_value' => '80.0000',
    ]);

    $items = [
        ['product_id' => $product1->id, 'product_variant_id' => null, 'quantity' => 1],
        ['product_id' => $product2->id, 'product_variant_id' => null, 'quantity' => 1],
    ];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1);
});

test('uses variant price when variant provided', function () {
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '50.0000']);
    $variant = ProductVariant::factory()->for($product)->create(['price' => '100.0000']);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'min_order_value' => '100.0000',
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($shippingRate->id);
});

test('returns array with correct structure', function () {
    $carrier = ShippingCarrier::factory()->active()->create(['name' => 'Test Carrier']);
    $region = Region::factory()->create(['countries' => []]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create([
        'name' => 'Standard Shipping',
        'rate' => '10.0000',
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligibleShippingOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    $first = $result->first();

    expect($first)->toHaveKeys(['id', 'name', 'carrier_name', 'type', 'rate', 'delivery_time'])
        ->and($first['id'])->toBe($shippingRate->id)
        ->and($first['name'])->toBeArray()
        ->and($first['carrier_name'])->toBeArray()
        ->and($first['rate'])->toBe('10.0000')
        ->and($first['delivery_time'])->toBeArray();
});
