<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Queries\DashboardStatsQuery;
use Illuminate\Support\Collection;

covers(DashboardStatsQuery::class);

uses()->group('queries', 'dashboard');

test('returns expected structure', function () {
    $query = new DashboardStatsQuery();
    $result = $query->execute();

    expect($result)->toBeArray()
        ->toHaveKeys(['stats', 'salesChart', 'recentOrders', 'topProducts']);

    expect($result['stats'])->toBeArray()
        ->toHaveKeys([
            'totalRevenue',
            'totalOrders',
            'totalCustomers',
            'revenueChange',
            'ordersChange',
            'customersChange',
            'averageOrderValue',
        ]);

    expect($result['salesChart'])->toBeArray()->toBeEmpty();

    expect($result['recentOrders'])->toBeInstanceOf(Collection::class);

    expect($result['topProducts'])->toBeInstanceOf(Collection::class);
});

test('returns correct totals when no data exists', function () {
    $query = new DashboardStatsQuery();
    $stats = $query->execute()['stats'];

    expect($stats['totalRevenue'])->toBe('0.0000')
        ->and($stats['totalOrders'])->toBe(0)
        ->and($stats['totalCustomers'])->toBe(0)
        ->and($stats['averageOrderValue'])->toBe('0.0000');
});

test('calculates totals correctly with paid orders', function () {
    $user = User::factory()->customer()->create();
    $product = Product::factory()->create(['title' => ['en' => 'Test Product']]);

    $order1 = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 10.00,
        'created_at' => now(),
    ]);

    $order2 = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 200.00,
        'refund_total' => 0.00,
        'created_at' => now(),
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 50.00,
        'created_at' => now(),
    ]);

    OrderItem::factory()->create(['order_id' => $order1->id, 'product_id' => $product->id, 'quantity' => 2, 'total_price' => 90.00]);
    OrderItem::factory()->create(['order_id' => $order2->id, 'product_id' => $product->id, 'quantity' => 1, 'total_price' => 200.00]);

    $query = new DashboardStatsQuery();
    $stats = $query->execute()['stats'];

    // totalOrders counts only paid orders; AOV = revenue / paid orders
    expect($stats['totalRevenue'])->toBe('290.0000')
        ->and($stats['totalOrders'])->toBe(2)
        ->and($stats['totalCustomers'])->toBe(1)
        ->and($stats['averageOrderValue'])->toBe('145.0000');
});

test('filters stats and chart by date range', function () {
    $user = User::factory()->customer()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 0.00,
        'created_at' => now()->subDays(2),
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 200.00,
        'refund_total' => 0.00,
        'created_at' => now()->subDays(10),
    ]);

    $query = new DashboardStatsQuery();
    $result = $query->execute(now()->subDays(6)->startOfDay(), now()->endOfDay());

    expect($result['stats']['totalRevenue'])->toBe('100.0000')
        ->and($result['stats']['totalOrders'])->toBe(1)
        ->and($result['salesChart'])->toHaveCount(7);
});

test('normalizes multi-currency orders to base currency in revenue', function () {
    $user = User::factory()->customer()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 0.00,
        'exchange_rate' => 1.0,
        'currency_code' => 'USD',
        'created_at' => now(),
    ]);

    // EUR order at 0.85 rate means 170 EUR = 200 USD in base currency
    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 170.00,
        'refund_total' => 0.00,
        'exchange_rate' => 0.85,
        'currency_code' => 'EUR',
        'created_at' => now(),
    ]);

    $query = new DashboardStatsQuery();
    $stats = $query->execute()['stats'];

    // 100/1.0 + 170/0.85 = 100 + 200 = 300
    expect($stats['totalRevenue'])->toBe('300.0000');
});

test('normalizes multi-currency revenue in chart data', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 0.00,
        'exchange_rate' => 1.0,
        'created_at' => now(),
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 170.00,
        'refund_total' => 0.00,
        'exchange_rate' => 0.85,
        'created_at' => now(),
    ]);

    $query = new DashboardStatsQuery();
    $chart = $query->execute()['salesChart'];

    $todaySales = collect($chart)->firstWhere('date', now()->format('M d'));
    expect($todaySales['net_sales'])->toBe('300.0000');
});

test('normalizes multi-currency revenue in top products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['title' => ['en' => 'Test Product']]);

    $usdOrder = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'exchange_rate' => 1.0,
    ]);

    $eurOrder = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'exchange_rate' => 0.85,
    ]);

    OrderItem::factory()->create(['order_id' => $usdOrder->id, 'product_id' => $product->id, 'quantity' => 1, 'total_price' => 100.00]);
    OrderItem::factory()->create(['order_id' => $eurOrder->id, 'product_id' => $product->id, 'quantity' => 1, 'total_price' => 85.00]);

    $query = new DashboardStatsQuery();
    $topProducts = $query->execute()['topProducts'];

    // 100/1.0 + 85/0.85 = 100 + 100 = 200
    expect($topProducts->first()['revenue'])->toBe('200.0000');
});

test('calculates period over previous period changes correctly', function () {
    $user = User::factory()->customer()->create();

    for ($i = 0; $i < 5; $i++) {
        Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Paid,
            'total' => 100.00,
            'refund_total' => 0.00,
            'created_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Paid,
            'total' => 50.00,
            'refund_total' => 0.00,
            'created_at' => now()->subDays(35 + $i),
        ]);
    }

    $query = new DashboardStatsQuery();
    $stats = $query->execute()['stats'];

    expect($stats['revenueChange'])->toBe(233.3)
        ->and($stats['ordersChange'])->toBe(66.7)
        ->and($stats['customersChange'])->toBe(100.0);
});

test('handles zero old values in percentage calculation', function () {
    $user = User::factory()->customer()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 0.00,
        'created_at' => now(),
    ]);

    $query = new DashboardStatsQuery();
    $stats = $query->execute()['stats'];

    expect($stats['revenueChange'])->toBe(100.0)
        ->and($stats['ordersChange'])->toBe(100.0)
        ->and($stats['customersChange'])->toBe(100.0);
});

test('returns 30 days of revenue data', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        Order::factory()->create([
            'customer_id' => $user->id,
            'payment_status' => PaymentStatus::Paid,
            'total' => 100.00,
            'refund_total' => 0.00,
            'created_at' => now()->subDays($i),
        ]);
    }

    $query = new DashboardStatsQuery();
    $chart = $query->execute()['salesChart'];

    expect($chart)->toHaveCount(30);

    expect($chart[0]['date'])->toBe(now()->subDays(29)->format('M d'))
        ->and($chart[29]['date'])->toBe(now()->format('M d'));

    $totalSales = array_sum(array_column($chart, 'net_sales'));
    expect($totalSales)->toBeGreaterThan(0);
});

test('excludes unpaid orders from revenue', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'created_at' => now(),
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 100.00,
        'created_at' => now(),
    ]);

    $query = new DashboardStatsQuery();
    $chart = $query->execute()['salesChart'];

    $todaySales = collect($chart)->firstWhere('date', now()->format('M d'));
    expect($todaySales['net_sales'])->toBe('100.0000');
});

test('returns latest 5 orders with correct fields', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 7; $i++) {
        Order::factory()->create([
            'customer_id' => $user->id,
            'customer_email' => "email{$i}@example.com",
            'total' => 100.00 + $i,
            'fulfillment_status' => 'unfulfilled',
            'created_at' => now()->subHours($i),
        ]);
    }

    $query = new DashboardStatsQuery();
    $recentOrders = $query->execute()['recentOrders'];

    expect($recentOrders)->toHaveCount(5);

    $firstOrder = $recentOrders->first();
    expect($firstOrder)->toHaveKeys([
        'id', 'customer_email', 'total', 'fulfillment_status', 'created_at',
    ]);
});

test('returns products ordered by total sold', function () {
    $user = User::factory()->create();

    $product1 = Product::factory()->create(['title' => ['en' => 'Product 1']]);
    $product2 = Product::factory()->create(['title' => ['en' => 'Product 2']]);
    $product3 = Product::factory()->create(['title' => ['en' => 'Product 3']]);

    $order1 = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);
    $order2 = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order1->id,
        'product_id' => $product1->id,
        'quantity' => 5,
        'total_price' => 250.00,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order2->id,
        'product_id' => $product1->id,
        'quantity' => 5,
        'total_price' => 250.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order1->id,
        'product_id' => $product2->id,
        'quantity' => 4,
        'total_price' => 200.00,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order2->id,
        'product_id' => $product2->id,
        'quantity' => 4,
        'total_price' => 200.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order1->id,
        'product_id' => $product3->id,
        'quantity' => 3,
        'total_price' => 150.00,
    ]);

    $query = new DashboardStatsQuery();
    $topProducts = $query->execute()['topProducts'];

    expect($topProducts)->toHaveCount(3);

    expect($topProducts->first()['title'])->toBe(['en' => 'Product 1'])
        ->and($topProducts->first()['total_sold'])->toBe(10)
        ->and($topProducts->first()['revenue'])->toBe('500.0000');

    expect($topProducts->skip(1)->first()['title'])->toBe(['en' => 'Product 2'])
        ->and($topProducts->skip(1)->first()['total_sold'])->toBe(8);

    expect($topProducts->last()['title'])->toBe(['en' => 'Product 3'])
        ->and($topProducts->last()['total_sold'])->toBe(3);
});

test('excludes unpaid orders from top products', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['title' => ['en' => 'Test Product']]);

    $paidOrder = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $unpaidOrder = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $paidOrder->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'total_price' => 250.00,
    ]);

    OrderItem::factory()->create([
        'order_id' => $unpaidOrder->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'total_price' => 250.00,
    ]);

    $query = new DashboardStatsQuery();
    $topProducts = $query->execute()['topProducts'];

    expect($topProducts)->toHaveCount(1)
        ->and($topProducts->first()['total_sold'])->toBe(5)
        ->and($topProducts->first()['revenue'])->toBe('250.0000');
});

test('handles refunds correctly in revenue', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 200.00,
        'refund_total' => 50.00,
        'created_at' => now(),
    ]);

    $query = new DashboardStatsQuery();
    $chart = $query->execute()['salesChart'];

    $todaySales = collect($chart)->firstWhere('date', now()->format('M d'));
    expect($todaySales['net_sales'])->toBe('150.0000');
});
