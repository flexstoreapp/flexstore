<?php

declare(strict_types=1);

use App\Actions\RestockOrderItemsAction;
use App\Enums\StockMovementReason;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;

covers(RestockOrderItemsAction::class);

uses()->group('actions', 'inventory');

test('restocks items with tracked stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 5]);
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => '10.0000',
        'total_price' => '30.0000',
        'tax_amount' => '0.0000',
    ]);

    $entries = collect([['item' => $orderItem, 'quantity' => 3]]);

    app(RestockOrderItemsAction::class)->handle(
        $user,
        $entries,
        StockMovementReason::Cancellation,
        Order::class,
        $order->id,
    );

    $product->refresh();
    expect($product->stock)->toBe(8);

    $movement = StockMovement::query()->where('product_id', $product->id)->latest('id')->first();
    expect($movement->quantity)->toBe(3)
        ->and($movement->reason)->toBe(StockMovementReason::Cancellation)
        ->and($movement->reference_type)->toBe(Order::class)
        ->and($movement->reference_id)->toBe((string) $order->id);
});

test('skips items without tracked stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['track_stock' => false, 'stock' => null]);
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '10.0000',
        'total_price' => '20.0000',
        'tax_amount' => '0.0000',
    ]);

    $entries = collect([['item' => $orderItem, 'quantity' => 2]]);

    app(RestockOrderItemsAction::class)->handle(
        $user,
        $entries,
        StockMovementReason::Cancellation,
        Order::class,
        $order->id,
    );

    expect(StockMovement::query()->where('product_id', $product->id)->exists())->toBeFalse();
});

test('skips items without a product', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => null,
        'quantity' => 1,
        'unit_price' => '10.0000',
        'total_price' => '10.0000',
        'tax_amount' => '0.0000',
    ]);

    $entries = collect([['item' => $orderItem, 'quantity' => 1]]);

    app(RestockOrderItemsAction::class)->handle(
        $user,
        $entries,
        StockMovementReason::Cancellation,
        Order::class,
        $order->id,
    );

    expect(StockMovement::query()->count())->toBe(0);
});

test('restocks variant stock when item has a variant', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['track_stock' => false]);
    $variant = ProductVariant::factory()->for($product)->create(['track_stock' => true, 'stock' => 10]);
    $order = Order::factory()->create();
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    $entries = collect([['item' => $orderItem, 'quantity' => 4]]);

    app(RestockOrderItemsAction::class)->handle(
        $user,
        $entries,
        StockMovementReason::Return,
        Order::class,
        $order->id,
    );

    $variant->refresh();
    expect($variant->stock)->toBe(14);
});

test('handles multiple items in a single call', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['track_stock' => true, 'stock' => 2]);
    $productB = Product::factory()->create(['track_stock' => true, 'stock' => 7]);
    $order = Order::factory()->create();

    $itemA = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $productA->id,
        'quantity' => 1,
        'unit_price' => '10.0000',
        'total_price' => '10.0000',
        'tax_amount' => '0.0000',
    ]);

    $itemB = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $productB->id,
        'quantity' => 3,
        'unit_price' => '20.0000',
        'total_price' => '60.0000',
        'tax_amount' => '0.0000',
    ]);

    $entries = collect([
        ['item' => $itemA, 'quantity' => 1],
        ['item' => $itemB, 'quantity' => 3],
    ]);

    app(RestockOrderItemsAction::class)->handle(
        $user,
        $entries,
        StockMovementReason::Cancellation,
        Order::class,
        $order->id,
    );

    $productA->refresh();
    $productB->refresh();
    expect($productA->stock)->toBe(3)
        ->and($productB->stock)->toBe(10);
});

test('handles empty collection gracefully', function () {
    $user = User::factory()->create();

    app(RestockOrderItemsAction::class)->handle(
        $user,
        collect(),
        StockMovementReason::Cancellation,
        Order::class,
        1,
    );

    expect(StockMovement::query()->count())->toBe(0);
});
