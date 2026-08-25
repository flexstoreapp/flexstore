<?php

declare(strict_types=1);

use App\Actions\UpdateOrderItemAction;
use App\DTOs\HydratedOrderItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\castAsJson;

covers(UpdateOrderItemAction::class);

uses()->group('actions', 'order');

/**
 * @param  ?array<string, mixed>  $variantOptions
 */
function hydratedForUpdate(Product $product, ?ProductVariant $variant, int $quantity, string $unitPrice, string $totalPrice, ?array $variantOptions = []): HydratedOrderItem
{
    return new HydratedOrderItem(
        product: $product,
        variant: $variant,
        unitPrice: $unitPrice,
        totalPrice: $totalPrice,
        quantity: $quantity,
        variantOptions: $variantOptions,
        productTitle: $product->getTranslations('title'),
        productSku: (string) $product->sku,
        variantTitle: $variant?->title,
        requiresShipping: (bool) $product->requires_shipping,
        weight: $product->weight,
        weightUnit: $product->weight_unit,
    );
}

test('updates an order item without variant', function () {
    $order = Order::factory()->create();
    $originalProduct = Product::factory()->create(['price' => '50.0000']);
    $newProduct = Product::factory()->create(['price' => '60.0000']);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $originalProduct->id,
        'product_variant_id' => null,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $newProduct, null, hydratedForUpdate($newProduct, null, 3, '60.0000', '180.0000', []));

    expect($result)->toBeInstanceOf(OrderItem::class)
        ->and($result->id)->toBe($orderItem->id)
        ->and($result->product_id)->toBe($newProduct->id)
        ->and($result->product_variant_id)->toBeNull()
        ->and($result->quantity)->toBe(3)
        ->and($result->unit_price)->toBe('60.0000')
        ->and($result->total_price)->toBe('180.0000')
        ->and($result->tax_amount)->toBe('0.0000');

    assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'product_id' => $newProduct->id,
        'product_variant_id' => null,
        'quantity' => 3,
        'unit_price' => '60.0000',
    ]);
});

test('updates an order item with variant', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();
    $oldVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '50.0000',
    ]);
    $newVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $oldVariant->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $product, $newVariant, hydratedForUpdate($product, $newVariant, 2, '75.0000', '150.0000', ['Size' => 'Large']));

    expect($result->id)->toBe($orderItem->id)
        ->and($result->product_variant_id)->toBe($newVariant->id)
        ->and($result->quantity)->toBe(2)
        ->and($result->unit_price)->toBe('75.0000')
        ->and($result->variant_options)->toEqual(['Size' => 'Large']);

    assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'product_variant_id' => $newVariant->id,
        'quantity' => 2,
    ]);
});

test('updates quantity only', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '50.0000']);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $product, null, hydratedForUpdate($product, null, 5, '50.0000', '250.0000', []));

    expect($result->quantity)->toBe(5)
        ->and($result->total_price)->toBe('250.0000')
        ->and($result->product_id)->toBe($product->id);
});

test('resets tax amount to zero on update', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'tax_amount' => '10.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $product, null, hydratedForUpdate($product, null, 2, '50.0000', '100.0000', []));

    expect($result->tax_amount)->toBe('0.0000');

    assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'tax_amount' => '0.0000',
    ]);
});

test('updates variant options', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'variant_options' => ['Color' => 'Red'],
    ]);

    $newOptions = [
        'Color' => 'Blue',
        'Size' => 'Large',
        'Material' => 'Cotton',
    ];

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $product, $variant, hydratedForUpdate($product, $variant, 1, '50.0000', '50.0000', $newOptions));

    expect($result->variant_options)->toEqual($newOptions);

    assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'variant_options' => castAsJson($newOptions),
    ]);
});

test('re-snapshots cost when the item is swapped to a different product', function () {
    $order = Order::factory()->create();
    $originalProduct = Product::factory()->create(['cost_per_item' => '20.0000']);
    $newProduct = Product::factory()->create(['cost_per_item' => '35.0000']);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $originalProduct->id,
        'product_variant_id' => null,
        'cost_per_item' => '20.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $newProduct, null, hydratedForUpdate($newProduct, null, 1, '50.0000', '50.0000', []));

    expect($result->cost_per_item)->toBe('35.0000');

    assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'product_id' => $newProduct->id,
        'cost_per_item' => '35.0000',
    ]);
});

test('prefers variant cost over product cost on update', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['cost_per_item' => '20.0000']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
        'cost_per_item' => '35.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'cost_per_item' => '20.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $product, $variant, hydratedForUpdate($product, $variant, 1, '75.0000', '75.0000', []));

    expect($result->cost_per_item)->toBe('35.0000');
});

test('falls back to product cost on update when variant cost is null', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['cost_per_item' => '20.0000']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
        'cost_per_item' => null,
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'cost_per_item' => '10.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $product, $variant, hydratedForUpdate($product, $variant, 1, '75.0000', '75.0000', []));

    expect($result->cost_per_item)->toBe('20.0000');
});

test('switches from variant to no variant', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '50.0000']);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'unit_price' => '75.0000',
    ]);

    $result = app(UpdateOrderItemAction::class)->handle($orderItem, $product, null, hydratedForUpdate($product, null, 1, '50.0000', '50.0000', []));

    expect($result->product_variant_id)->toBeNull()
        ->and($result->unit_price)->toBe('50.0000');

    assertDatabaseHas('order_items', [
        'id' => $orderItem->id,
        'product_variant_id' => null,
        'unit_price' => '50.0000',
    ]);
});
