<?php

declare(strict_types=1);

use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Prunable;

covers(CheckoutSession::class);

uses()->group('models', 'checkout');

test('has factory', function () {
    expect(CheckoutSession::factory())->toBeInstanceOf(Factory::class);
});

test('uses uuid primary key', function () {
    $session = new CheckoutSession();

    expect($session->getKeyType())->toBe('string')
        ->and($session->getIncrementing())->toBeFalse();
});

test('has translatable attributes', function () {
    $session = new CheckoutSession();

    expect($session->getTranslatableAttributes())->toBe(['shipping_carrier_name', 'shipping_rate_name', 'region_name', 'payment_gateway_name']);
});

test('belongs to cart relationship', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->create(['cart_id' => $cart->id]);

    expect($session->cart)->toBeInstanceOf(Cart::class)
        ->and($session->cart->id)->toBe($cart->id);
});

test('belongs to customer relationship', function () {
    $customer = User::factory()->create();
    $session = CheckoutSession::factory()->create(['customer_id' => $customer->id]);

    expect($session->customer)->toBeInstanceOf(User::class)
        ->and($session->customer->id)->toBe($customer->id);
});

test('handles null customer relationship', function () {
    $session = CheckoutSession::factory()->create(['customer_id' => null]);

    expect($session->customer)->toBeNull();
});

test('belongs to payment gateway relationship', function () {
    $paymentGateway = PaymentGateway::factory()->create();
    $session = CheckoutSession::factory()->create(['payment_gateway_id' => $paymentGateway->id]);

    expect($session->paymentGateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($session->paymentGateway->id)->toBe($paymentGateway->id);
});

test('belongs to coupon relationship', function () {
    $coupon = Coupon::factory()->create();
    $session = CheckoutSession::factory()->create(['coupon_id' => $coupon->id]);

    expect($session->coupon)->toBeInstanceOf(Coupon::class)
        ->and($session->coupon->id)->toBe($coupon->id);
});

test('handles null coupon relationship', function () {
    $session = CheckoutSession::factory()->create(['coupon_id' => null]);

    expect($session->coupon)->toBeNull();
});

test('belongs to order relationship', function () {
    $order = Order::factory()->create();
    $session = CheckoutSession::factory()->create(['order_id' => $order->id]);

    expect($session->order)->toBeInstanceOf(Order::class)
        ->and($session->order->id)->toBe($order->id);
});

test('handles null order relationship', function () {
    $session = CheckoutSession::factory()->create(['order_id' => null]);

    expect($session->order)->toBeNull();
});

test('has many reservations relationship', function () {
    $session = CheckoutSession::factory()->create();
    StockReservation::factory()->count(3)->create(['checkout_session_id' => $session->id]);

    expect($session->reservations)->toHaveCount(3)
        ->and($session->reservations->first())->toBeInstanceOf(StockReservation::class);
});

test('sessions are retained rather than pruned on a schedule', function () {
    $uses = class_uses_recursive(CheckoutSession::class);

    expect($uses)->not->toContain(Prunable::class)
        ->and($uses)->not->toContain(MassPrunable::class)
        ->and(method_exists(CheckoutSession::class, 'prunable'))->toBeFalse();
});

test('was_recovered is false when no recovery email was sent', function () {
    $order = Order::factory()->create();
    $session = CheckoutSession::factory()->completed()->create([
        'order_id' => $order->id,
    ]);

    expect($session->was_recovered)->toBeFalse();
});

test('was_recovered is false when not yet completed', function () {
    $session = CheckoutSession::factory()->pending()->create([
        'order_id' => null,
    ]);

    expect($session->was_recovered)->toBeFalse();
});

test('updateReplacingTranslations clears stale locales on reassigned snapshot fields', function () {
    $session = CheckoutSession::factory()->create([
        'shipping_rate_name' => ['en' => 'Free Standard Shipping', 'bn' => 'ফ্রি স্ট্যান্ডার্ড শিপিং', 'ar' => 'شحن عادي مجاني'],
        'shipping_carrier_name' => ['en' => 'Standard Carrier', 'bn' => 'স্ট্যান্ডার্ড', 'ar' => 'قياسي'],
    ]);

    $session->updateReplacingTranslations([
        'shipping_rate_name' => ['en' => 'Priority Mail'],
        'shipping_carrier_name' => ['en' => 'Shippo'],
    ]);

    expect($session->refresh()->getTranslations('shipping_rate_name'))->toBe(['en' => 'Priority Mail'])
        ->and($session->getTranslations('shipping_carrier_name'))->toBe(['en' => 'Shippo']);
});
