<?php

declare(strict_types=1);

use App\Actions\ConfirmCheckoutSessionAction;
use App\DTOs\PaymentSettlement;
use App\Enums\CheckoutSessionStatus;
use App\Enums\OrderActivityType;
use App\Enums\OrderAddressType;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Payment\PaymentManager;
use Illuminate\Support\Facades\Notification;

covers(ConfirmCheckoutSessionAction::class);

uses()->group('actions', 'checkout');

function createConfirmableSession(array $overrides = []): CheckoutSession
{
    $product = $overrides['product'] ?? Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => false,
    ]);

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '5.0000']);
    $gateway = $overrides['gateway'] ?? PaymentGateway::factory()->cod()->active()->create();

    return CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'customer_email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'billing_address' => null,
        'different_billing_address' => false,
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $carrier->id,
        'shipping_carrier_name' => $carrier->getTranslations('name'),
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
        'region_id' => $region->id,
        'region_name' => $region->getTranslations('name'),
        'payment_gateway_id' => $gateway->id,
        'payment_gateway_name' => $gateway->getTranslations('name'),
        'coupon_id' => $overrides['coupon_id'] ?? null,
        'coupon_code' => $overrides['coupon_code'] ?? null,
        'items' => [[
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => $overrides['quantity'] ?? 1,
            'unit_price' => '50.0000',
            'total_price' => '50.0000',
            'product_title' => $product->getTranslations('title'),
            'product_sku' => $product->sku,
            'variant_title' => null,
            'variant_options' => [],
            'weight' => $product->weight,
            'weight_unit' => $product->weight_unit,
        ]],
        'currency_code' => 'USD',
        'exchange_rate' => '1.0000',
        'prices_include_tax' => $overrides['prices_include_tax'] ?? false,
        'shipping_is_taxable' => $overrides['shipping_is_taxable'] ?? false,
        'tax_based_on' => $overrides['tax_based_on'] ?? 'shipping',
        'default_tax_rate' => $overrides['default_tax_rate'] ?? null,
        'tax_store_country_code' => $overrides['tax_store_country_code'] ?? null,
        'tax_store_state' => $overrides['tax_store_state'] ?? null,
        'tax_store_postal_code' => $overrides['tax_store_postal_code'] ?? null,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '55.0000',
    ]);
}

test('idempotent double-confirmation returns same order', function () {
    $session = createConfirmableSession();
    $action = app(ConfirmCheckoutSessionAction::class);

    $order1 = $action->handle($session, new PaymentSettlement(status: PaymentStatus::Paid, gatewayReference: 'txn_123'));
    $order2 = $action->handle($session->refresh(), new PaymentSettlement(status: PaymentStatus::Paid, gatewayReference: 'txn_123'));

    expect($order1->id)->toBe($order2->id)
        ->and(Order::query()->count())->toBe(1);
});

test('idempotent confirmation backfills payment status when redirect confirmed with unpaid', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $session = createConfirmableSession(['gateway' => $gateway]);
    $action = app(ConfirmCheckoutSessionAction::class);

    $order = $action->handle($session, new PaymentSettlement(status: PaymentStatus::Unpaid, gatewayReference: null));
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);

    $order = $action->handle($session->refresh(), new PaymentSettlement(status: PaymentStatus::Paid, gatewayReference: 'pi_webhook_abc'));
    expect($order->payment_status)->toBe(PaymentStatus::Paid);

    $transaction = OrderTransaction::where('order_id', $order->id)
        ->where('gateway_reference', 'pi_webhook_abc')
        ->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::Sale)
        ->and($transaction->status)->toBe(TransactionStatus::Success);
});

test('coupon usage incremented exactly once on double-confirmation', function () {
    $coupon = Coupon::factory()->active()->valid()->create(['used_count' => 0]);
    $session = createConfirmableSession([
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
    ]);

    $action = app(ConfirmCheckoutSessionAction::class);
    $action->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));
    $action->handle($session->refresh(), new PaymentSettlement(status: PaymentStatus::Paid));

    expect($coupon->refresh()->used_count)->toBe(1);
});

test('stock reservations released after confirmation', function () {
    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => true,
        'stock' => 10,
    ]);

    $session = createConfirmableSession(['product' => $product]);

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    expect(StockReservation::where('checkout_session_id', $session->id)->count())->toBe(1);

    $action = app(ConfirmCheckoutSessionAction::class);
    $action->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    expect(StockReservation::where('checkout_session_id', $session->id)->count())->toBe(0);
});

test('payment status and gateway reference set on created order', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $session = createConfirmableSession(['gateway' => $gateway]);
    $action = app(ConfirmCheckoutSessionAction::class);

    $order = $action->handle($session, new PaymentSettlement(status: PaymentStatus::Paid, gatewayReference: 'pi_stripe_abc'));

    expect($order->payment_status)->toBe(PaymentStatus::Paid);

    $transaction = OrderTransaction::query()->where('order_id', $order->id)->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->gateway_reference)->toBe('pi_stripe_abc')
        ->and($transaction->type)->toBe(TransactionType::Sale)
        ->and($transaction->status)->toBe(TransactionStatus::Success);
});

test('creates order with null gateway reference when none provided', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $session = createConfirmableSession(['gateway' => $gateway]);
    $action = app(ConfirmCheckoutSessionAction::class);

    $order = $action->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    expect($order->payment_status)->toBe(PaymentStatus::Paid);

    $transaction = OrderTransaction::query()->where('order_id', $order->id)->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->gateway_reference)->toBeNull();
});

test('cancels session and returns null for failed payment', function (PaymentStatus $status) {
    $session = createConfirmableSession();

    StockReservation::factory()->create([
        'checkout_session_id' => $session->id,
        'product_id' => $session->items[0]['product_id'],
        'quantity' => 1,
    ]);

    $action = app(ConfirmCheckoutSessionAction::class);
    $result = $action->handle($session, new PaymentSettlement(status: $status));

    expect($result)->toBeNull()
        ->and($session->refresh()->status)->toBe(CheckoutSessionStatus::Canceled)
        ->and(Order::query()->count())->toBe(0)
        ->and(StockReservation::where('checkout_session_id', $session->id)->count())->toBe(0);
})->with([
    'failed' => PaymentStatus::Failed,
    'canceled' => PaymentStatus::Canceled,
]);

test('creates order items from session data', function () {
    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => false,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '60.0000',
        'title' => 'Large',
        'track_stock' => false,
    ]);

    $cart = Cart::factory()->create(['subtotal' => '110.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '5.0000']);
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'customer_email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'billing_address' => null,
        'different_billing_address' => false,
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $carrier->id,
        'shipping_carrier_name' => $carrier->getTranslations('name'),
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
        'region_id' => $region->id,
        'region_name' => $region->getTranslations('name'),
        'payment_gateway_id' => $gateway->id,
        'payment_gateway_name' => $gateway->getTranslations('name'),
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
                'unit_price' => '50.0000',
                'total_price' => '50.0000',
                'product_title' => $product->getTranslations('title'),
                'product_sku' => $product->sku,
                'variant_title' => null,
                'variant_options' => [],
                'weight' => $product->weight,
                'weight_unit' => $product->weight_unit,
            ],
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'unit_price' => '60.0000',
                'total_price' => '60.0000',
                'product_title' => $product->getTranslations('title'),
                'product_sku' => $variant->sku ?? $product->sku,
                'variant_title' => 'Large',
                'variant_options' => ['size' => 'Large'],
                'weight' => $product->weight,
                'weight_unit' => $product->weight_unit,
            ],
        ],
        'currency_code' => 'USD',
        'exchange_rate' => '1.0000',
        'prices_include_tax' => false,
        'subtotal' => '110.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '115.0000',
    ]);

    $order = app(ConfirmCheckoutSessionAction::class)->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    $items = OrderItem::query()->where('order_id', $order->id)->get();

    expect($items)->toHaveCount(2);

    $baseItem = $items->whereNull('product_variant_id')->first();
    expect($baseItem->product_id)->toBe($product->id)
        ->and($baseItem->unit_price)->toBe('50.0000')
        ->and($baseItem->total_price)->toBe('50.0000')
        ->and($baseItem->quantity)->toBe(1);

    $variantItem = $items->whereNotNull('product_variant_id')->first();
    expect($variantItem->product_variant_id)->toBe($variant->id)
        ->and($variantItem->unit_price)->toBe('60.0000')
        ->and($variantItem->total_price)->toBe('60.0000')
        ->and($variantItem->variant_title)->toBe('Large');
});

test('decrements stock on confirmation', function () {
    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => true,
        'stock' => 10,
    ]);

    $session = createConfirmableSession(['product' => $product, 'quantity' => 3]);

    $action = app(ConfirmCheckoutSessionAction::class);
    $order = $action->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    expect($product->refresh()->stock)->toBe(7);

    $stockMovement = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('quantity', -3)
        ->where('reason', StockMovementReason::Sale)
        ->where('reference_type', Order::class)
        ->where('reference_id', $order->id)
        ->first();

    expect($stockMovement)->not->toBeNull();
});

test('confirms canceled session when webhook arrives with successful payment', function () {
    $session = createConfirmableSession();

    $session->update(['status' => CheckoutSessionStatus::Canceled]);

    $action = app(ConfirmCheckoutSessionAction::class);
    $order = $action->handle($session->refresh(), new PaymentSettlement(status: PaymentStatus::Paid, gatewayReference: 'pi_webhook_123'));

    expect($order)->toBeInstanceOf(Order::class)
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($session->refresh()->status)->toBe(CheckoutSessionStatus::Completed)
        ->and($session->order_id)->toBe($order->id);
});

test('creates order with session shipping and payment details', function () {
    $session = createConfirmableSession();
    $order = app(ConfirmCheckoutSessionAction::class)->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    expect($order->shipping_carrier_id)->toBe($session->shipping_carrier_id)
        ->and($order->shipping_rate_id)->toBe($session->shipping_rate_id)
        ->and($order->region_id)->toBe($session->region_id)
        ->and($order->payment_gateway_id)->toBe($session->payment_gateway_id)
        ->and($order->subtotal)->toBe('50.0000')
        ->and($order->shipping_total)->toBe('5.0000')
        ->and($order->currency_code)->toBe('USD')
        ->and($order->exchange_rate)->toBe('1.0000');

    expect($order->addresses)->toHaveCount(2);
    $shippingAddr = $order->addresses->where('type', OrderAddressType::Shipping)->first();
    expect($shippingAddr->address_line_1)->toBe('123 Main St');
});

test('rejects confirmation of session at contact information step', function () {
    $session = CheckoutSession::factory()->initiated()->create([
        'customer_email' => 'test@example.com',
    ]);

    $action = app(ConfirmCheckoutSessionAction::class);

    expect(fn () => $action->handle($session, new PaymentSettlement(status: PaymentStatus::Paid)))
        ->toThrow(RuntimeException::class, 'Cannot confirm checkout session in contact_information step.');
});

test('does not send customer notification when setting is disabled', function () {
    Notification::fake();

    Setting::setValue('notification_customer_order_confirmed', false);
    Setting::setValue('notification_admin_new_order', false);

    $session = createConfirmableSession();
    app(ConfirmCheckoutSessionAction::class)->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    Notification::assertNothingSent();
});

test('sends customer notification when setting is enabled', function () {
    Notification::fake();

    Setting::setValue('notification_customer_order_confirmed', true);
    Setting::setValue('notification_admin_new_order', false);

    $customer = User::factory()->create();
    $session = createConfirmableSession();
    $session->update(['customer_id' => $customer->id]);

    app(ConfirmCheckoutSessionAction::class)->handle($session->refresh(), new PaymentSettlement(status: PaymentStatus::Paid));

    Notification::assertSentTo($customer, CustomerOrderConfirmedNotification::class);
});

test('does not send admin notification when setting is disabled', function () {
    Notification::fake();

    Setting::setValue('notification_admin_new_order', false);
    Setting::setValue('notification_customer_order_confirmed', false);

    $session = createConfirmableSession();
    app(ConfirmCheckoutSessionAction::class)->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    Notification::assertNothingSent();
});

test('records order placed activity on confirmation', function () {
    $session = createConfirmableSession();
    $order = app(ConfirmCheckoutSessionAction::class)->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    $activity = $order->activities()->where('type', OrderActivityType::OrderPlaced)->first();
    expect($activity)->not->toBeNull()
        ->and($activity->type)->toBe(OrderActivityType::OrderPlaced);
});

test('records payment received activity for non-manual gateway', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    PaymentManager::fake();

    $session = createConfirmableSession(['gateway' => $gateway]);
    $order = app(ConfirmCheckoutSessionAction::class)->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    $activity = $order->activities()->where('type', OrderActivityType::PaymentReceived)->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['from_status'])->toBe(PaymentStatus::Unpaid->value)
        ->and($activity->metadata['to_status'])->toBe(PaymentStatus::Paid->value)
        ->and($activity->metadata['transaction_id'])->not->toBeNull();
});

test('transfers all tax settings from session to order', function () {
    $session = createConfirmableSession([
        'prices_include_tax' => true,
        'shipping_is_taxable' => true,
        'tax_based_on' => 'billing',
        'default_tax_rate' => '7.5000',
        'tax_store_country_code' => 'US',
        'tax_store_state' => 'TX',
        'tax_store_postal_code' => '73301',
    ]);

    $order = app(ConfirmCheckoutSessionAction::class)->handle($session, new PaymentSettlement(status: PaymentStatus::Paid));

    expect($order->prices_include_tax)->toBeTrue()
        ->and($order->shipping_is_taxable)->toBeTrue()
        ->and($order->tax_based_on)->toBe('billing')
        ->and($order->default_tax_rate)->toBe('7.5000')
        ->and($order->tax_store_country_code)->toBe('US')
        ->and($order->tax_store_state)->toBe('TX')
        ->and($order->tax_store_postal_code)->toBe('73301');
});

test('LTV stays at zero when an order is created (still unfulfilled)', function () {
    $customer = User::factory()->create(['lifetime_value' => '0.0000']);
    $session = createConfirmableSession();
    $session->update(['customer_id' => $customer->id, 'customer_email' => $customer->email]);

    app(ConfirmCheckoutSessionAction::class)->handle($session->refresh(), new PaymentSettlement(status: PaymentStatus::Paid, gatewayReference: 'txn_paid'));

    expect($customer->fresh()->lifetime_value)->toBe('0.0000');
});

test('LTV updates when fulfilled order from a normal checkout completes', function () {
    $customer = User::factory()->create(['lifetime_value' => '0.0000']);
    $session = createConfirmableSession();
    $session->update(['customer_id' => $customer->id, 'customer_email' => $customer->email]);

    $order = app(ConfirmCheckoutSessionAction::class)->handle($session->refresh(), new PaymentSettlement(status: PaymentStatus::Paid, gatewayReference: 'txn_paid'));
    app(App\Actions\TransitionFulfillmentStatusAction::class)
        ->handle($order, App\Enums\FulfillmentStatus::Fulfilled);

    expect($customer->fresh()->lifetime_value)->toBe($order->fresh()->net_paid_total);
});
