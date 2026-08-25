<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Queries\DuplicateOrderSourceQuery;

covers(DuplicateOrderSourceQuery::class);

uses()->group('queries', 'order');

test('refreshes item snapshots to current product data', function () {
    $product = Product::factory()->create([
        'title' => 'Original title',
        'sku' => 'ORIG-SKU',
        'price' => '10.0000',
    ]);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['quantity' => 2, 'unit_price' => '10.0000', 'total_price' => '20.0000']);

    $product->update([
        'title' => 'Updated title',
        'sku' => 'NEW-SKU',
        'price' => '15.0000',
    ]);

    $result = app(DuplicateOrderSourceQuery::class)->execute($order->fresh());

    $item = $result->items->first();

    expect($item->getTranslation('product_title', 'en'))->toBe('Updated title')
        ->and($item->product_sku)->toBe('NEW-SKU')
        ->and($item->unit_price)->toBe('15.0000')
        ->and($item->total_price)->toBe('30.0000');
});

test('recalculates subtotal and total from refreshed prices', function () {
    $product = Product::factory()->create(['price' => '10.0000']);
    $order = Order::factory()->create([
        'subtotal' => '20.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '2.0000',
        'tax_total' => '1.0000',
        'total' => '24.0000',
    ]);
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['quantity' => 2, 'unit_price' => '10.0000', 'total_price' => '20.0000']);

    $product->update(['price' => '25.0000']);

    $result = app(DuplicateOrderSourceQuery::class)->execute($order->fresh());

    expect($result->subtotal)->toBe('50.0000')
        ->and($result->total)->toBe('50.0000');
});

test('filters out items whose product was deleted', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->forProduct($product)->create();
    OrderItem::factory()->forOrder($order)->create(['product_id' => null]);

    $result = app(DuplicateOrderSourceQuery::class)->execute($order->fresh());

    expect($result->items)->toHaveCount(1)
        ->and($result->items->first()->product_id)->toBe($product->id);
});

test('returns zero subtotal when all items are filtered out', function () {
    $order = Order::factory()->create([
        'subtotal' => '10.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'tax_total' => '0.0000',
        'total' => '10.0000',
    ]);
    OrderItem::factory()->forOrder($order)->create(['product_id' => null]);

    $result = app(DuplicateOrderSourceQuery::class)->execute($order->fresh());

    expect($result->items)->toBeEmpty()
        ->and($result->subtotal)->toBe('0.0000')
        ->and($result->total)->toBe('0.0000');
});
