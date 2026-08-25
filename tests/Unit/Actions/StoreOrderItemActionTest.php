<?php

declare(strict_types=1);

use App\Actions\StoreOrderItemAction;
use App\DTOs\HydratedOrderItem;
use App\Enums\DimensionUnit;
use App\Enums\WeightUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\castAsJson;

covers(StoreOrderItemAction::class);

uses()->group('actions', 'order');

/**
 * @param  array<string, mixed>  $data
 */
function buildHydrated(Product $product, ?ProductVariant $variant, array $data): HydratedOrderItem
{
    return new HydratedOrderItem(
        product: $product,
        variant: $variant,
        unitPrice: (string) $data['unit_price'],
        totalPrice: (string) $data['total_price'],
        quantity: (int) $data['quantity'],
        variantOptions: $data['variant_options'] ?? null,
        productTitle: is_array($data['product_title']) ? $data['product_title'] : ['en' => (string) $data['product_title']],
        productSku: (string) $data['product_sku'],
        variantTitle: $data['variant_title'] ?? null,
        requiresShipping: true,
        weight: $data['weight'] ?? null,
        weightUnit: isset($data['weight_unit']) ? (WeightUnit::tryFrom((string) $data['weight_unit'])) : null,
    );
}

test('creates an order item with variant', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
    ]);

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => $variant->title,
        'variant_options' => ['Size' => 'Large', 'Color' => 'Red'],
        'quantity' => 1,
        'unit_price' => '75.0000',
        'total_price' => '75.0000',
        'weight' => '2.0',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, $variant, buildHydrated($product, $variant, $data));

    expect($result)->toBeInstanceOf(OrderItem::class)
        ->and($result->product_variant_id)->toBe($variant->id)
        ->and($result->variant_title)->toBe($variant->title)
        ->and($result->unit_price)->toBe('75.0000')
        ->and($result->variant_options)->toEqual(['Size' => 'Large', 'Color' => 'Red']);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'variant_title' => $variant->title,
    ]);
});

test('creates multiple order items for same order', function () {
    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => '50.0000']);
    $product2 = Product::factory()->create(['price' => '75.0000']);

    $data1 = [
        'product_title' => $product1->title,
        'product_sku' => $product1->sku,
        'variant_title' => null,
        'variant_options' => [],
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
        'weight' => '1.0',
        'weight_unit' => 'kg',
    ];

    $data2 = [
        'product_title' => $product2->title,
        'product_sku' => $product2->sku,
        'variant_title' => null,
        'variant_options' => [],
        'quantity' => 1,
        'unit_price' => '75.0000',
        'total_price' => '75.0000',
        'weight' => '0.5',
        'weight_unit' => 'kg',
    ];

    $result1 = app(StoreOrderItemAction::class)->handle($order, $product1, null, buildHydrated($product1, null, $data1));
    $result2 = app(StoreOrderItemAction::class)->handle($order, $product2, null, buildHydrated($product2, null, $data2));

    expect($result1->product_id)->toBe($product1->id)
        ->and($result1->quantity)->toBe(2)
        ->and($result2->product_id)->toBe($product2->id)
        ->and($result2->quantity)->toBe(1);

    assertDatabaseCount('order_items', 2);
});

test('initializes tax amount to zero', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => null,
        'variant_options' => [],
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
        'weight' => '1.0',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, null, buildHydrated($product, null, $data));

    expect($result->tax_amount)->toBe('0.0000');

    assertDatabaseHas('order_items', [
        'id' => $result->id,
        'tax_amount' => '0.0000',
    ]);
});

test('snapshots dimensions onto the order item', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $item = new HydratedOrderItem(
        product: $product,
        variant: null,
        unitPrice: '50.0000',
        totalPrice: '50.0000',
        quantity: 1,
        variantOptions: [],
        productTitle: ['en' => 'Widget'],
        productSku: 'SKU-1',
        variantTitle: null,
        requiresShipping: true,
        weight: '1.50',
        weightUnit: WeightUnit::Kg,
        length: '30',
        width: '22',
        height: '10',
        dimensionUnit: DimensionUnit::Cm,
    );

    $result = app(StoreOrderItemAction::class)->handle($order, $product, null, $item);

    expect($result->length)->toBe('30.00')
        ->and($result->width)->toBe('22.00')
        ->and($result->height)->toBe('10.00')
        ->and($result->dimension_unit)->toBe('cm');

    assertDatabaseHas('order_items', [
        'id' => $result->id,
        'length' => '30.00',
        'width' => '22.00',
        'height' => '10.00',
        'dimension_unit' => 'cm',
    ]);
});

test('snapshots product cost onto the order item', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '50.0000', 'cost_per_item' => '20.0000']);

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => null,
        'variant_options' => [],
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
        'weight' => '1.0',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, null, buildHydrated($product, null, $data));

    expect($result->cost_per_item)->toBe('20.0000');

    assertDatabaseHas('order_items', [
        'id' => $result->id,
        'cost_per_item' => '20.0000',
    ]);
});

test('prefers variant cost over product cost', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['cost_per_item' => '20.0000']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
        'cost_per_item' => '35.0000',
    ]);

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => $variant->title,
        'variant_options' => [],
        'quantity' => 1,
        'unit_price' => '75.0000',
        'total_price' => '75.0000',
        'weight' => '1.0',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, $variant, buildHydrated($product, $variant, $data));

    expect($result->cost_per_item)->toBe('35.0000');
});

test('falls back to product cost when variant cost is null', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['cost_per_item' => '20.0000']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
        'cost_per_item' => null,
    ]);

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => $variant->title,
        'variant_options' => [],
        'quantity' => 1,
        'unit_price' => '75.0000',
        'total_price' => '75.0000',
        'weight' => '1.0',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, $variant, buildHydrated($product, $variant, $data));

    expect($result->cost_per_item)->toBe('20.0000');
});

test('snapshots null cost when neither product nor variant has a cost', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['cost_per_item' => null]);

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => null,
        'variant_options' => [],
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
        'weight' => '1.0',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, null, buildHydrated($product, null, $data));

    expect($result->cost_per_item)->toBeNull();

    assertDatabaseHas('order_items', [
        'id' => $result->id,
        'cost_per_item' => null,
    ]);
});

test('snapshots full cost even when the unit price is discounted', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '50.0000', 'cost_per_item' => '20.0000']);

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => null,
        'variant_options' => [],
        'quantity' => 1,
        'unit_price' => '30.0000',
        'total_price' => '30.0000',
        'weight' => '1.0',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, null, buildHydrated($product, null, $data));

    expect($result->unit_price)->toBe('30.0000')
        ->and($result->cost_per_item)->toBe('20.0000');
});

test('handles variant options as array', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $variantOptions = [
        'Size' => 'Medium',
        'Color' => 'Blue',
        'Material' => 'Cotton',
    ];

    $data = [
        'product_title' => $product->title,
        'product_sku' => $product->sku,
        'variant_title' => $variant->title,
        'variant_options' => $variantOptions,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
        'weight' => '0.8',
        'weight_unit' => 'kg',
    ];

    $result = app(StoreOrderItemAction::class)->handle($order, $product, $variant, buildHydrated($product, $variant, $data));

    expect($result->variant_options)->toEqual($variantOptions);

    assertDatabaseHas('order_items', [
        'id' => $result->id,
        'variant_options' => castAsJson($variantOptions),
    ]);
});
