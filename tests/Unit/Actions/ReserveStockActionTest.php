<?php

declare(strict_types=1);

use App\Actions\ReserveStockAction;
use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockReservation;

covers(ReserveStockAction::class);

uses()->group('actions', 'stock');

test('creates reservations for items', function () {
    $session = CheckoutSession::factory()->pending()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);
    $expiresAt = now()->addMinutes(10);

    $action = app(ReserveStockAction::class);
    $action->handle($session, [
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 3],
    ], $expiresAt);

    expect(StockReservation::query()->count())->toBe(1);

    $reservation = StockReservation::query()->first();
    expect($reservation->checkout_session_id)->toBe($session->id)
        ->and($reservation->product_id)->toBe($product->id)
        ->and($reservation->product_variant_id)->toBeNull()
        ->and($reservation->quantity)->toBe(3);
});

test('creates reservations for items with variants', function () {
    $session = CheckoutSession::factory()->pending()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'track_stock' => true,
        'stock' => 5,
    ]);
    $expiresAt = now()->addMinutes(10);

    $action = app(ReserveStockAction::class);
    $action->handle($session, [
        ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 2],
    ], $expiresAt);

    $reservation = StockReservation::query()->first();
    expect($reservation->product_variant_id)->toBe($variant->id)
        ->and($reservation->quantity)->toBe(2);
});

test('cleans up expired reservations for same products', function () {
    $oldSession = CheckoutSession::factory()->pending()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);

    StockReservation::factory()->create([
        'checkout_session_id' => $oldSession->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 5,
        'expires_at' => now()->subMinutes(5),
    ]);

    $newSession = CheckoutSession::factory()->pending()->create();
    $expiresAt = now()->addMinutes(10);

    $action = app(ReserveStockAction::class);
    $action->handle($newSession, [
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 3],
    ], $expiresAt);

    expect(StockReservation::query()->count())->toBe(1)
        ->and(StockReservation::query()->first()->checkout_session_id)->toBe($newSession->id);
});

test('skips items with zero quantity', function () {
    $session = CheckoutSession::factory()->pending()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);
    $expiresAt = now()->addMinutes(10);

    $action = app(ReserveStockAction::class);
    $action->handle($session, [
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 0],
    ], $expiresAt);

    expect(StockReservation::query()->count())->toBe(0);
});

test('does not clean up active reservations for same products', function () {
    $existingSession = CheckoutSession::factory()->pending()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 10]);

    StockReservation::factory()->create([
        'checkout_session_id' => $existingSession->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 2,
        'expires_at' => now()->addMinutes(5),
    ]);

    $newSession = CheckoutSession::factory()->pending()->create();
    $expiresAt = now()->addMinutes(10);

    $action = app(ReserveStockAction::class);
    $action->handle($newSession, [
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 3],
    ], $expiresAt);

    expect(StockReservation::query()->count())->toBe(2);
});
