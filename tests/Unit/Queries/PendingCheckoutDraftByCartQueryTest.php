<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\Enums\CheckoutSessionStatus;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Queries\PendingCheckoutDraftByCartQuery;

covers(PendingCheckoutDraftByCartQuery::class);

uses()->group('checkout');

test('returns the latest pending draft for a cart', function () {
    $cart = Cart::factory()->create();

    CheckoutSession::factory()->create([
        'cart_id' => $cart->id,
        'status' => CheckoutSessionStatus::Pending,
        'customer_email' => 'old@example.com',
    ]);

    $address = \App\Models\CustomerAddress::factory()->create();
    $latest = CheckoutSession::factory()->create([
        'cart_id' => $cart->id,
        'status' => CheckoutSessionStatus::Pending,
        'customer_email' => 'buyer@example.com',
        'different_billing_address' => true,
        'notes' => 'Leave at the door',
        'customer_address_id' => $address->id,
    ]);

    $draft = app(PendingCheckoutDraftByCartQuery::class)->execute($cart->id);

    expect($draft)->not->toBeNull()
        ->and($draft['customer_email'])->toBe('buyer@example.com')
        ->and($draft['different_billing_address'])->toBeTrue()
        ->and($draft['notes'])->toBe('Leave at the door')
        ->and($draft['customer_address_id'])->toBe($address->id)
        ->and($draft)->toHaveKeys(['shipping_address', 'billing_address'])
        ->and($latest->customer_email)->toBe('buyer@example.com');
});

test('ignores non-pending sessions', function () {
    $cart = Cart::factory()->create();

    CheckoutSession::factory()->create([
        'cart_id' => $cart->id,
        'status' => CheckoutSessionStatus::Completed,
    ]);

    expect(app(PendingCheckoutDraftByCartQuery::class)->execute($cart->id))->toBeNull();
});

test('returns null when the cart has no draft', function () {
    expect(app(PendingCheckoutDraftByCartQuery::class)->execute(Cart::factory()->create()->id))->toBeNull();
});
