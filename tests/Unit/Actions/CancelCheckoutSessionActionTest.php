<?php

declare(strict_types=1);

use App\Actions\CancelCheckoutSessionAction;
use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\StockReservation;

covers(CancelCheckoutSessionAction::class);

uses()->group('actions', 'checkout');

test('cancels a pending session and releases reservations', function () {
    $session = CheckoutSession::factory()->pending()->create();
    $product = Product::factory()->create();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $action = app(CancelCheckoutSessionAction::class);
    $action->handle($session);

    expect($session->refresh()->status)->toBe(CheckoutSessionStatus::Canceled)
        ->and(StockReservation::where('checkout_session_id', $session->id)->count())->toBe(0);
});

test('no-op when session is already completed', function () {
    $session = CheckoutSession::factory()->completed()->create();

    $action = app(CancelCheckoutSessionAction::class);
    $action->handle($session);

    expect($session->refresh()->status)->toBe(CheckoutSessionStatus::Completed);
});

test('no-op when session is already canceled', function () {
    $session = CheckoutSession::factory()->canceled()->create();

    $action = app(CancelCheckoutSessionAction::class);
    $action->handle($session);

    expect($session->refresh()->status)->toBe(CheckoutSessionStatus::Canceled);
});
