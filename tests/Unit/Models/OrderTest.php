<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CancellationReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderAddressType;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderTaxDetail;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\User;
use App\Payment\Drivers\CodDriver;
use App\Payment\PaymentManager;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(Order::class);

uses()->group('models', 'order');

test('has factory', function () {
    expect(Order::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $order = new Order();
    $casts = $order->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('fulfillment_status', FulfillmentStatus::class)
        ->toHaveKey('payment_status', PaymentStatus::class)
        ->toHaveKey('prices_include_tax', 'boolean')
        ->toHaveKey('shipping_is_taxable', 'boolean')
        ->toHaveKey('subtotal', 'decimal:4')
        ->toHaveKey('tax_total', 'decimal:4')
        ->toHaveKey('shipping_total', 'decimal:4')
        ->toHaveKey('discount_total', 'decimal:4')
        ->toHaveKey('total', 'decimal:4')
        ->toHaveKey('paid_total', 'decimal:4')
        ->toHaveKey('refund_total', 'decimal:4')
        ->toHaveKey('net_paid_total', 'decimal:4')
        ->toHaveKey('balance_due_total', 'decimal:4')
        ->toHaveKey('credit_due_total', 'decimal:4')
        ->toHaveKey('default_tax_rate', 'decimal:4')
        ->toHaveKey('exchange_rate', 'decimal:4')
        ->toHaveKey('cancellation_reason', CancellationReason::class)
        ->toHaveKey('canceled_at', 'datetime');
});

test('belongs to customer relationship', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    expect($order->customer)->toBeInstanceOf(User::class)
        ->and($order->customer->id)->toBe($customer->id);
});

test('has many items relationship', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->forOrder($order)->count(3)->create();

    expect($order->items)->toHaveCount(3)
        ->and($order->items->first())->toBeInstanceOf(OrderItem::class);
});

test('has many tax details relationship', function () {
    $order = Order::factory()->create();
    OrderTaxDetail::factory()->forOrder($order)->count(2)->create();

    expect($order->taxDetails)->toHaveCount(2)
        ->and($order->taxDetails->first())->toBeInstanceOf(OrderTaxDetail::class);
});

test('belongs to payment gateway relationship', function () {
    $paymentGateway = PaymentGateway::factory()->create();
    $order = Order::factory()->create(['payment_gateway_id' => $paymentGateway->id]);

    expect($order->paymentGateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($order->paymentGateway->id)->toBe($paymentGateway->id);
});

test('belongs to shipping carrier relationship', function () {
    $shippingCarrier = ShippingCarrier::factory()->create();
    $order = Order::factory()->create(['shipping_carrier_id' => $shippingCarrier->id]);

    expect($order->shippingCarrier)->toBeInstanceOf(ShippingCarrier::class)
        ->and($order->shippingCarrier->id)->toBe($shippingCarrier->id);
});

test('belongs to region relationship', function () {
    $region = Region::factory()->create();
    $order = Order::factory()->create(['region_id' => $region->id]);

    expect($order->region)->toBeInstanceOf(Region::class)
        ->and($order->region->id)->toBe($region->id);
});

test('handles null region relationship', function () {
    $order = Order::factory()->create(['region_id' => null]);

    expect($order->region)->toBeNull();
});

test('has many addresses relationship', function () {
    $order = Order::factory()->create();
    OrderAddress::factory(2)->forOrder($order)->create();

    expect($order->addresses)->toHaveCount(2)
        ->and($order->addresses->first())->toBeInstanceOf(OrderAddress::class);
});

test('has billing address relationship', function () {
    $order = Order::factory()->create();
    OrderAddress::factory()->forOrder($order)->create([
        'type' => OrderAddressType::Billing,
    ]);

    expect($order->billingAddress)->toBeInstanceOf(OrderAddress::class)
        ->and($order->billingAddress->type)->toBe(OrderAddressType::Billing);
});

test('has shipping address relationship', function () {
    $order = Order::factory()->create();
    OrderAddress::factory()->forOrder($order)->create([
        'type' => OrderAddressType::Shipping,
    ]);

    expect($order->shippingAddress)->toBeInstanceOf(OrderAddress::class)
        ->and($order->shippingAddress->type)->toBe(OrderAddressType::Shipping);
});

test('has many activities relationship', function () {
    $order = Order::factory()->create();
    OrderActivity::factory(3)->create(['order_id' => $order->id]);

    expect($order->activities)->toHaveCount(3)
        ->and($order->activities->first())->toBeInstanceOf(OrderActivity::class);
});

test('belongs to coupon relationship', function () {
    $coupon = Coupon::factory()->create();
    $order = Order::factory()->create([
        'coupon_id' => $coupon->id,
    ]);

    expect($order->coupon)->toBeInstanceOf(Coupon::class)
        ->and($order->coupon->id)->toBe($coupon->id);
});

test('handles null coupon relationship', function () {
    $order = Order::factory()->create(['coupon_id' => null]);

    expect($order->coupon)->toBeNull();
});

test('has many refunds relationship', function () {
    $order = Order::factory()->create();
    $refund = OrderRefund::factory()->create([
        'order_id' => $order->id,
    ]);

    expect($order->refunds)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($order->refunds->first())->toBeInstanceOf(OrderRefund::class)
        ->and($order->refunds->first()->id)->toBe($refund->id);
});

test('handles empty refunds collection', function () {
    $order = Order::factory()->create();

    expect($order->refunds)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($order->refunds)->toHaveCount(0);
});

test('isRefundable returns true when payment status is paid', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);

    expect($order->is_refundable)->toBeTrue();
});

test('isRefundable returns true when payment status is partially paid', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PartiallyPaid]);

    expect($order->is_refundable)->toBeTrue();
});

test('isRefundable returns true when payment status is partially refunded', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::PartiallyRefunded]);

    expect($order->is_refundable)->toBeTrue();
});

test('isRefundable returns false when payment status is unpaid', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Unpaid]);

    expect($order->is_refundable)->toBeFalse();
});

test('isRefundable returns false when payment status is refunded', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Refunded]);

    expect($order->is_refundable)->toBeFalse();
});

test('isRefundable returns false when payment status is failed', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Failed]);

    expect($order->is_refundable)->toBeFalse();
});

test('refundTotal accessor returns zero when no refunds', function () {
    $order = Order::factory()->create();

    expect($order->refund_total)->toBe('0.0000');
});

test('refundTotal returns stored value', function () {
    $order = Order::factory()->create([
        'refund_total' => '56.2500',
    ]);

    expect($order->refund_total)->toBe('56.2500');
});

test('refundTotal handles decimal precision', function () {
    $order = Order::factory()->create([
        'refund_total' => '31.0000',
    ]);

    expect($order->refund_total)->toBe('31.0000');
});

test('failed factory state creates order', function () {
    $order = Order::factory()->failed()->create();

    expect($order->payment_status)->toBe(PaymentStatus::Failed)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('getOriginalPaymentMethod returns method and details from successful sale transaction', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'payment_method' => 'card',
        'payment_method_details' => ['brand' => 'visa', 'last4' => '4242'],
    ]);

    [$method, $details] = $order->getOriginalPaymentMethod();

    expect($method)->toBe('card')
        ->and($details)->toBe(['brand' => 'visa', 'last4' => '4242']);
});

test('has many shipments relationship', function () {
    $order = Order::factory()->create();
    $shipment = \App\Models\OrderShipment::factory()->create(['order_id' => $order->id]);

    expect($order->shipments)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($order->shipments->first())->toBeInstanceOf(\App\Models\OrderShipment::class)
        ->and($order->shipments->first()->id)->toBe($shipment->id);
});

test('getOriginalPaymentMethod returns nulls when no sale transaction has payment method', function () {
    $order = Order::factory()->create(['payment_status' => PaymentStatus::Paid]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
    ]);

    [$method, $details] = $order->getOriginalPaymentMethod();

    expect($method)->toBeNull()
        ->and($details)->toBeNull();
});

test('has_outstanding_balance returns true when balance_due_total is positive', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'balance_due_total' => '50.0000',
    ]);

    expect($order->has_outstanding_balance)->toBeTrue();
});

test('has_outstanding_balance returns false when balance_due_total is zero', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'balance_due_total' => '0.0000',
    ]);

    expect($order->has_outstanding_balance)->toBeFalse();
});

test('has_credit_owed returns true when credit_due_total is positive', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'paid_total' => '100.0000',
        'credit_due_total' => '10.0000',
    ]);

    expect($order->has_credit_owed)->toBeTrue();
});

test('has_credit_owed returns false when credit_due_total is zero', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'credit_due_total' => '0.0000',
    ]);

    expect($order->has_credit_owed)->toBeFalse();
});

test('is_voidable returns true for manual gateway with positive paid_total', function () {
    PaymentManager::fake(new CodDriver());

    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
        'paid_total' => '100.0000',
    ]);

    expect($order->is_voidable)->toBeTrue();
});

test('is_voidable returns false for non-manual gateway', function () {
    PaymentManager::fake();

    $gateway = PaymentGateway::factory()->active()->create();
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
        'paid_total' => '100.0000',
    ]);

    expect($order->is_voidable)->toBeFalse();
});

test('usesManualPayment returns true for manual gateway', function () {
    PaymentManager::fake(new CodDriver());

    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->create(['payment_gateway_id' => $gateway->id]);

    expect($order->usesManualPayment())->toBeTrue();
});

test('usesManualPayment returns false for non-manual gateway', function () {
    PaymentManager::fake();

    $gateway = PaymentGateway::factory()->active()->create();
    $order = Order::factory()->create(['payment_gateway_id' => $gateway->id]);

    expect($order->usesManualPayment())->toBeFalse();
});

test('usesManualPayment returns false when no payment gateway', function () {
    $order = Order::factory()->create(['payment_gateway_id' => null]);

    expect($order->usesManualPayment())->toBeFalse();
});

test('is_canceled returns true when canceled_at is set', function () {
    $order = Order::factory()->create(['canceled_at' => now()]);

    expect($order->is_canceled)->toBeTrue();
});

test('is_canceled returns false when canceled_at is null', function () {
    $order = Order::factory()->create(['canceled_at' => null]);

    expect($order->is_canceled)->toBeFalse();
});

test('is_cancellable returns true for non-canceled unfulfilled order', function () {
    $order = Order::factory()->create([
        'canceled_at' => null,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    expect($order->is_cancellable)->toBeTrue();
});

test('is_cancellable returns false when order is already canceled', function () {
    $order = Order::factory()->canceled()->create();

    expect($order->is_cancellable)->toBeFalse();
});

test('is_cancellable returns false when fulfillment status is fulfilled', function () {
    $order = Order::factory()->create([
        'canceled_at' => null,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
    ]);

    expect($order->is_cancellable)->toBeFalse();
});

test('is_voidable returns false when paid_total is zero', function () {
    PaymentManager::fake(new CodDriver());

    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'payment_gateway_id' => $gateway->id,
        'paid_total' => '0.0000',
    ]);

    expect($order->is_voidable)->toBeFalse();
});
