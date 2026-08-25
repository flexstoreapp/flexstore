<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Queries\CustomerStatsByEmailQuery;

covers(CustomerStatsByEmailQuery::class);

uses()->group('queries');

test('returns null when no user and no orders match the email', function () {
    $result = (new CustomerStatsByEmailQuery())->execute('nobody@example.com');

    expect($result)->toBeNull();
});

test('returns user stats when a registered customer matches the email', function () {
    $user = User::factory()->create([
        'email' => 'pat@example.com',
        'name' => 'Pat Logged-in',
        'lifetime_value' => '500.0000',
    ]);
    Order::factory()->count(2)->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
    ]);
    Order::factory()->create(['customer_email' => 'someone-else@example.com']);

    $result = (new CustomerStatsByEmailQuery())->execute($user->email);

    expect($result)->toMatchArray([
        'name' => 'Pat Logged-in',
        'orders_count' => 2,
        'lifetime_value' => '500.0000',
    ])->and($result['created_at'])->toBe($user->created_at->toIso8601String());
});

test('aggregates lifetime value from past orders for guest emails', function () {
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'paid_total' => '100.0000',
        'net_paid_total' => '100.0000',
        'exchange_rate' => '1.0000',
        'created_at' => now()->subDays(30),
    ]);
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'payment_status' => PaymentStatus::Paid,
        'total' => '50.0000',
        'paid_total' => '50.0000',
        'net_paid_total' => '50.0000',
        'exchange_rate' => '1.0000',
        'created_at' => now()->subDays(10),
    ]);

    $result = (new CustomerStatsByEmailQuery())->execute('guest@example.com');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBeNull()
        ->and($result['orders_count'])->toBe(2)
        ->and($result['lifetime_value'])->toBe('150.0000');
});

test('uses earliest order created_at as customer_since for guests', function () {
    $earliest = now()->subMonths(2)->startOfDay();
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'created_at' => $earliest,
    ]);
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'created_at' => now()->subDays(5),
    ]);

    $result = (new CustomerStatsByEmailQuery())->execute('guest@example.com');

    expect($result['created_at'])->toBe($earliest->toIso8601String());
});

test('excludes canceled and unpaid orders from lifetime value aggregation', function () {
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'payment_status' => PaymentStatus::Paid,
        'total' => '80.0000',
        'paid_total' => '80.0000',
        'net_paid_total' => '80.0000',
        'exchange_rate' => '1.0000',
        'canceled_at' => null,
    ]);
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'payment_status' => PaymentStatus::Paid,
        'total' => '999.0000',
        'paid_total' => '999.0000',
        'net_paid_total' => '999.0000',
        'exchange_rate' => '1.0000',
        'canceled_at' => now(),
    ]);
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'payment_status' => PaymentStatus::Unpaid,
        'total' => '70.0000',
    ]);

    $result = (new CustomerStatsByEmailQuery())->execute('guest@example.com');

    expect($result['orders_count'])->toBe(2)
        ->and($result['lifetime_value'])->toBe('80.0000');
});

test('excludes canceled orders from the orders count', function () {
    $user = User::factory()->create([
        'email' => 'pat@example.com',
        'lifetime_value' => '0.0000',
    ]);
    Order::factory()->count(2)->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'canceled_at' => null,
    ]);
    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'canceled_at' => now(),
    ]);

    $result = (new CustomerStatsByEmailQuery())->execute($user->email);

    expect($result['orders_count'])->toBe(2);
});

test('converts foreign-currency net_paid_total back to base currency via exchange rate', function () {
    Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
        'payment_status' => PaymentStatus::Paid,
        'total' => '200.0000',
        'paid_total' => '200.0000',
        'net_paid_total' => '200.0000',
        'exchange_rate' => '2.0000',
    ]);

    $result = (new CustomerStatsByEmailQuery())->execute('guest@example.com');

    expect($result['lifetime_value'])->toBe('100.0000');
});
