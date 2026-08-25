<?php

declare(strict_types=1);

use App\Actions\ApplyCheckoutOptionsAction;
use App\DTOs\CheckoutOptionsInput;
use App\Enums\CheckoutSessionStatus;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

covers(ApplyCheckoutOptionsAction::class, CheckoutOptionsInput::class);

uses()->group('actions', 'checkout');

test('applies shipping and payment options to both cart and pending session', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'status' => CheckoutSessionStatus::Pending,
    ]);
    $shippingRate = ShippingRate::factory()->create();
    $paymentGateway = PaymentGateway::query()->firstOrCreate(
        ['driver' => 'cod'],
        ['name' => ['en' => 'Cash on delivery'], 'is_active' => true],
    );
    $user = User::factory()->create();

    app(ApplyCheckoutOptionsAction::class)->handle(
        cart: $cart->fresh(),
        input: CheckoutOptionsInput::fromArray([
            'shipping_rate_id' => $shippingRate->id,
            'payment_gateway_id' => $paymentGateway->id,
        ]),
        user: $user,
        currencyCode: 'USD',
    );

    expect($cart->fresh()->shipping_rate_id)->toBe($shippingRate->id)
        ->and($cart->fresh()->payment_gateway_id)->toBe($paymentGateway->id);

    expect($session->fresh()->shipping_rate_id)->toBe($shippingRate->id)
        ->and($session->fresh()->payment_gateway_id)->toBe($paymentGateway->id);
});

test('rolls back the cart write when the session write fails', function () {
    $cart = Cart::factory()->create(['shipping_rate_id' => null]);
    $shippingRate = ShippingRate::factory()->create();

    DB::shouldReceive('transaction')->andThrow(new QueryException('mysql', 'simulated', [], new RuntimeException('boom')));

    expect(fn () => app(ApplyCheckoutOptionsAction::class)->handle(
        cart: $cart->fresh(),
        input: CheckoutOptionsInput::fromArray(['shipping_rate_id' => $shippingRate->id]),
        user: null,
        currencyCode: 'USD',
    ))->toThrow(QueryException::class);

    expect($cart->fresh()->shipping_rate_id)->toBeNull();
});
