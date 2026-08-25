<?php

declare(strict_types=1);

use App\Actions\RecalculateCustomerLifetimeValueAction;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

covers(RecalculateCustomerLifetimeValueAction::class);

uses()->group('actions', 'customer');

test('calculates lifetime value for customer with single order', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '150.0000',
        'net_paid_total' => '150.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('150.0000');
});

test('calculates lifetime value for customer with multiple orders', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'net_paid_total' => '100.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '50.0000',
        'net_paid_total' => '50.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('150.0000');
});

test('excludes canceled orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'net_paid_total' => '100.0000',
    ]);

    Order::factory()->canceled()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'total' => '75.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('100.0000');
});

test('deducts refunded amounts from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::PartiallyRefunded,
        'total' => '100.0000',
        'refund_total' => '25.0000',
        'net_paid_total' => '75.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('75.0000');
});

test('excludes fully refunded orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Refunded,
        'total' => '100.0000',
        'refund_total' => '100.0000',
        'net_paid_total' => '0.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '50.0000',
        'net_paid_total' => '50.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('50.0000');
});

test('returns zero for customer with no orders', function () {
    $user = User::factory()->create();

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('0.0000');
});

test('returns zero for customer with only canceled orders', function () {
    $user = User::factory()->create();

    Order::factory()->canceled()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'total' => '100.0000',
    ]);

    Order::factory()->canceled()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'total' => '50.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('0.0000');
});

test('does nothing when customer is null', function () {
    DB::shouldReceive('update')->never();

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle(null);
});

test('handles orders with refunds totaling more than order total', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Refunded,
        'total' => '100.0000',
        'refund_total' => '150.0000',
        'net_paid_total' => '0.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '50.0000',
        'net_paid_total' => '50.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('50.0000');
});

test('correctly handles decimal precision', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '33.3300',
        'net_paid_total' => '33.3300',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '66.6700',
        'net_paid_total' => '66.6700',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('100.0000');
});

test('excludes unpaid orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => '100.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '50.0000',
        'net_paid_total' => '50.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('50.0000');
});

test('excludes failed payment orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Failed,
        'total' => '75.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '25.0000',
        'net_paid_total' => '25.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('25.0000');
});

test('excludes canceled payment orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Canceled,
        'total' => '150.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '50.0000',
        'net_paid_total' => '50.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('50.0000');
});

test('excludes unfulfilled orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '50.0000',
        'net_paid_total' => '50.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('50.0000');
});

test('excludes in-progress orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::InProgress,
        'payment_status' => PaymentStatus::Paid,
        'total' => '75.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '25.0000',
        'net_paid_total' => '25.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('25.0000');
});

test('excludes on hold orders from lifetime value', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::OnHold,
        'payment_status' => PaymentStatus::Paid,
        'total' => '200.0000',
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'net_paid_total' => '100.0000',
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    expect($user->lifetime_value)->toBe('100.0000');
});

test('normalizes multi-currency orders to base currency', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'exchange_rate' => 1.0,
    ]);

    // 170 EUR at 0.85 rate = 200 USD in base currency
    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'total' => '170.0000',
        'exchange_rate' => 0.85,
    ]);

    $action = app(RecalculateCustomerLifetimeValueAction::class);
    $action->handle($user);

    $user->refresh();

    // 100/1.0 + 170/0.85 = 100 + 200 = 300
    expect($user->lifetime_value)->toBe('300.0000');
});
