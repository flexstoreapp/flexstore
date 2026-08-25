<?php

declare(strict_types=1);

use App\Actions\ReleaseStockReservationsAction;
use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\StockReservation;

covers(ReleaseStockReservationsAction::class);

uses()->group('actions', 'checkout');

test('deletes all reservations for a checkout session', function () {
    $session = CheckoutSession::factory()->pending()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product1->id,
        'quantity' => 2,
    ]);

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product2->id,
        'quantity' => 1,
    ]);

    $action = new ReleaseStockReservationsAction();
    $action->handle($session);

    expect(StockReservation::where('checkout_session_id', $session->id)->count())->toBe(0);
});

test('does not affect reservations from other sessions', function () {
    $session = CheckoutSession::factory()->pending()->create();
    $otherSession = CheckoutSession::factory()->pending()->create();
    $product = Product::factory()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    StockReservation::factory()->create([
        'checkout_session_id' => $otherSession->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $action = new ReleaseStockReservationsAction();
    $action->handle($session);

    expect(StockReservation::where('checkout_session_id', $session->id)->count())->toBe(0)
        ->and(StockReservation::where('checkout_session_id', $otherSession->id)->count())->toBe(1);
});

test('handles session with no reservations gracefully', function () {
    $session = CheckoutSession::factory()->pending()->create();

    $action = new ReleaseStockReservationsAction();
    $action->handle($session);

    expect(StockReservation::where('checkout_session_id', $session->id)->count())->toBe(0);
});
