<?php

declare(strict_types=1);

use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;
use App\Queries\AvailableStockQuery;

covers(AvailableStockQuery::class);

uses()->group('queries', 'stock');

test('returns full stock when no reservations exist', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $query = app(AvailableStockQuery::class);

    expect($query->execute($product->id))->toBe(10);
});

test('subtracts active reservations from stock', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $session = CheckoutSession::factory()->pending()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 3,
        'expires_at' => now()->addMinutes(5),
    ]);

    $query = app(AvailableStockQuery::class);

    expect($query->execute($product->id))->toBe(7);
});

test('ignores expired reservations', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $session = CheckoutSession::factory()->pending()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 3,
        'expires_at' => now()->subMinutes(5),
    ]);

    $query = app(AvailableStockQuery::class);

    expect($query->execute($product->id))->toBe(10);
});

test('handles variant stock separately', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 20,
        'in_stock' => true,
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 8,
        'in_stock' => true,
    ]);

    $session = CheckoutSession::factory()->pending()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'expires_at' => now()->addMinutes(5),
    ]);

    $query = app(AvailableStockQuery::class);

    expect($query->execute($product->id, $variant->id))->toBe(6);
});

test('returns zero when reservations exceed stock', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 2,
        'in_stock' => true,
    ]);

    $session = CheckoutSession::factory()->pending()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 5,
        'expires_at' => now()->addMinutes(5),
    ]);

    $query = app(AvailableStockQuery::class);

    expect($query->execute($product->id))->toBe(0);
});

test('executeMany returns available stock for multiple items', function () {
    $product1 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $product2 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $session = CheckoutSession::factory()->pending()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product1->id,
        'product_variant_id' => null,
        'quantity' => 4,
        'expires_at' => now()->addMinutes(5),
    ]);

    $query = app(AvailableStockQuery::class);

    $result = $query->executeMany([
        ['product_id' => $product1->id, 'product_variant_id' => null],
        ['product_id' => $product2->id, 'product_variant_id' => null],
    ]);

    expect($result[$product1->id . ':null'])->toBe(6)
        ->and($result[$product2->id . ':null'])->toBe(5);
});

test('sums reservations from multiple sessions', function () {
    $product = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $session1 = CheckoutSession::factory()->pending()->create();
    $session2 = CheckoutSession::factory()->pending()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session1->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 3,
        'expires_at' => now()->addMinutes(5),
    ]);

    StockReservation::factory()->create([
        'checkout_session_id' => $session2->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 2,
        'expires_at' => now()->addMinutes(5),
    ]);

    $query = app(AvailableStockQuery::class);

    expect($query->execute($product->id))->toBe(5);
});

test('returns empty array for empty items', function () {
    $query = app(AvailableStockQuery::class);

    expect($query->executeMany([]))->toBe([]);
});
