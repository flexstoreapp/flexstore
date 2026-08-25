<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Queries\DuplicateOrderChangeQuery;

covers(DuplicateOrderChangeQuery::class);

uses()->group('queries', 'order');

test('returns empty when nothing has changed', function () {
    $product = Product::factory()->create(['price' => '10.0000']);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['unit_price' => '10.0000', 'product_sku' => $product->sku]);

    $changes = app(DuplicateOrderChangeQuery::class)->execute($order);

    expect($changes)->toBeEmpty();
});

test('detects product title change', function () {
    $product = Product::factory()->create(['title' => 'Original']);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['unit_price' => $product->price, 'product_sku' => $product->sku]);

    $product->update(['title' => 'Updated']);

    $changes = app(DuplicateOrderChangeQuery::class)->execute($order->fresh());

    expect($changes)->toHaveCount(1)
        ->and($changes[0]['type'])->toBe('updated');
});

test('detects product price change', function () {
    $product = Product::factory()->create(['price' => '10.0000']);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['unit_price' => '10.0000', 'product_sku' => $product->sku]);

    $product->update(['price' => '15.0000']);

    $changes = app(DuplicateOrderChangeQuery::class)->execute($order->fresh());

    expect($changes)->toHaveCount(1)
        ->and($changes[0]['type'])->toBe('updated');
});

test('detects product sku change', function () {
    $product = Product::factory()->create(['sku' => 'OLD-SKU']);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->create(['unit_price' => $product->price, 'product_sku' => 'OLD-SKU']);

    $product->update(['sku' => 'NEW-SKU']);

    $changes = app(DuplicateOrderChangeQuery::class)->execute($order->fresh());

    expect($changes)->toHaveCount(1)
        ->and($changes[0]['type'])->toBe('updated');
});

test('detects deleted product', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->create(['product_id' => null]);

    $changes = app(DuplicateOrderChangeQuery::class)->execute($order->fresh());

    expect($changes)->toHaveCount(1)
        ->and($changes[0]['type'])->toBe('removed');
});

test('deduplicates changes for same product', function () {
    $product = Product::factory()->create(['price' => '10.0000']);
    $order = Order::factory()->create();
    OrderItem::factory()
        ->forOrder($order)
        ->forProduct($product)
        ->count(2)
        ->create(['unit_price' => '10.0000', 'product_sku' => $product->sku]);

    $product->update(['price' => '20.0000']);

    $changes = app(DuplicateOrderChangeQuery::class)->execute($order->fresh());

    expect($changes)->toHaveCount(1);
});
