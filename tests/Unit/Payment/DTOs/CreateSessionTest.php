<?php

declare(strict_types=1);

use App\Enums\PaymentSessionPurpose;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\PaymentSession;
use App\Models\Setting;
use App\Payment\DTOs\CallbackUrls;
use App\Payment\DTOs\CreateSession;
use App\Payment\DTOs\RedirectUrls;

covers(CreateSession::class);
covers(CallbackUrls::class);
covers(RedirectUrls::class);

uses()->group('payment', 'dtos');

test('can be constructed directly', function () {
    $session = new CreateSession(
        internalReference: 'sess_123',
        amount: '100.0000',
        currencyCode: 'USD',
        redirectUrls: new RedirectUrls(returnUrl: 'https://example.com/success'),
        callbackUrls: new CallbackUrls(),
        customerEmail: 'test@example.com',
    );

    expect($session->internalReference)->toBe('sess_123')
        ->and($session->amount)->toBe('100.0000')
        ->and($session->currencyCode)->toBe('USD')
        ->and($session->customerEmail)->toBe('test@example.com')
        ->and($session->metadata)->toBe([])
        ->and($session->providerOptions)->toBe([]);
});

test('constructor accepts all optional parameters', function () {
    $redirectUrls = new RedirectUrls(
        returnUrl: 'https://example.com/success',
        cancelUrl: 'https://example.com/cancel',
        failureUrl: 'https://example.com/checkout',
    );

    $callbackUrls = new CallbackUrls(
        webhookUrl: 'https://example.com/webhook',
    );

    $session = new CreateSession(
        internalReference: 'sess_456',
        amount: '250.0000',
        currencyCode: 'EUR',
        customerEmail: 'customer@example.com',
        description: 'Test order',
        redirectUrls: $redirectUrls,
        callbackUrls: $callbackUrls,
        metadata: ['payment_session_id' => 'sess_456'],
        shippingAddress: ['city' => 'Munich'],
        billingAddress: ['city' => 'Berlin'],
        providerOptions: ['card_token' => 'tkn_test_123'],
    );

    expect($session->internalReference)->toBe('sess_456')
        ->and($session->amount)->toBe('250.0000')
        ->and($session->currencyCode)->toBe('EUR')
        ->and($session->customerEmail)->toBe('customer@example.com')
        ->and($session->description)->toBe('Test order')
        ->and($session->redirectUrls)->toBe($redirectUrls)
        ->and($session->callbackUrls)->toBe($callbackUrls)
        ->and($session->metadata)->toBe(['payment_session_id' => 'sess_456'])
        ->and($session->shippingAddress)->toBe(['city' => 'Munich'])
        ->and($session->billingAddress)->toBe(['city' => 'Berlin'])
        ->and($session->providerOptions)->toBe(['card_token' => 'tkn_test_123']);
});

test('fromCheckoutPaymentSession builds from checkout session', function () {
    Setting::setValue('store_name', 'FlexStore');

    $gateway = PaymentGateway::factory()->create();
    $checkoutSession = CheckoutSession::factory()->create([
        'customer_email' => 'shopper@example.com',
        'different_billing_address' => false,
        'shipping_address' => ['country_code' => 'US', 'city' => 'San Francisco'],
        'billing_address' => ['country_code' => 'GB', 'city' => 'London'],
    ]);
    $paymentSession = PaymentSession::factory()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkoutSession->id,
        'purpose' => PaymentSessionPurpose::Checkout,
        'amount' => '120.0000',
        'currency_code' => 'USD',
    ]);

    $session = CreateSession::fromCheckoutPaymentSession($paymentSession, $checkoutSession, ['extra' => 'value']);

    expect($session->internalReference)->toBe($paymentSession->id)
        ->and($session->amount)->toBe('120.0000')
        ->and($session->currencyCode)->toBe('USD')
        ->and($session->customerEmail)->toBe('shopper@example.com')
        ->and($session->storeName)->toBe('FlexStore')
        ->and($session->metadata)->toBe(['payment_session_id' => $paymentSession->id])
        ->and($session->shippingAddress)->toBe(['country_code' => 'US', 'city' => 'San Francisco'])
        ->and($session->billingAddress)->toBe(['country_code' => 'US', 'city' => 'San Francisco'])
        ->and($session->providerOptions)->toBe(['extra' => 'value'])
        ->and($session->redirectUrls->returnUrl)->toContain('/checkout/success')
        ->and($session->callbackUrls->webhookUrl)->toContain('/webhooks/payment/');
});

test('fromCheckoutPaymentSession uses billing address when different', function () {
    Setting::setValue('store_name', 'FlexStore');

    $gateway = PaymentGateway::factory()->create();
    $checkoutSession = CheckoutSession::factory()->create([
        'different_billing_address' => true,
        'shipping_address' => ['country_code' => 'US', 'city' => 'Austin'],
        'billing_address' => ['country_code' => 'GB', 'city' => 'Manchester'],
    ]);
    $paymentSession = PaymentSession::factory()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkoutSession->id,
    ]);

    $session = CreateSession::fromCheckoutPaymentSession($paymentSession, $checkoutSession);

    expect($session->billingAddress)->toBe(['country_code' => 'GB', 'city' => 'Manchester'])
        ->and($session->shippingAddress)->toBe(['country_code' => 'US', 'city' => 'Austin']);
});

test('CallbackUrls defaults webhook url to null', function () {
    $urls = new CallbackUrls();

    expect($urls->webhookUrl)->toBeNull();
});

test('CallbackUrls stores webhook url', function () {
    $urls = new CallbackUrls(webhookUrl: 'https://example.com/webhook');

    expect($urls->webhookUrl)->toBe('https://example.com/webhook');
});

test('RedirectUrls stores required return url with optional cancel and failure', function () {
    $urls = new RedirectUrls(
        returnUrl: 'https://example.com/ok',
        cancelUrl: 'https://example.com/cancel',
        failureUrl: 'https://example.com/fail',
    );

    expect($urls->returnUrl)->toBe('https://example.com/ok')
        ->and($urls->cancelUrl)->toBe('https://example.com/cancel')
        ->and($urls->failureUrl)->toBe('https://example.com/fail');
});

test('RedirectUrls defaults optional urls to null', function () {
    $urls = new RedirectUrls(returnUrl: 'https://example.com/ok');

    expect($urls->cancelUrl)->toBeNull()
        ->and($urls->failureUrl)->toBeNull();
});
