<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Enums\DatePeriod;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Requests\Admin\ShowDashboardRequest;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

covers(DashboardController::class, ShowDashboardRequest::class, DatePeriod::class);

uses()->group('dashboard');

test('displays dashboard', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['title' => ['en' => 'Test Product']]);
    $productMedia = Media::factory()->uploaded()->create();
    (new SyncMediaAction())->handle($product, [$productMedia->id]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'customer_email' => 'john@example.com',
        'total' => 100.00,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
    ]);

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'total_price' => 250.00,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('period', '30d')
                ->where('from', now()->subDays(29)->format('Y-m-d'))
                ->where('to', now()->format('Y-m-d'))
                ->has('stats')
                ->has('salesChart', 30)
                ->has('recentOrders')
                ->has('topProducts')
                ->where('stats.totalRevenue', fn ($value) => is_string($value))
                ->where('stats.totalOrders', fn ($value) => is_int($value))
                ->where('stats.totalCustomers', fn ($value) => is_int($value))
                ->where('stats.revenueChange', fn ($value) => is_numeric($value))
                ->where('stats.ordersChange', fn ($value) => is_numeric($value))
                ->where('stats.customersChange', fn ($value) => is_numeric($value))
                ->where('stats.averageOrderValue', fn ($value) => is_string($value))
                ->where('salesChart.0', fn ($day) => isset($day['date'], $day['net_sales']))
                ->where('salesChart.29', fn ($day) => isset($day['date'], $day['net_sales']))
                ->where('recentOrders', fn ($orders) => $orders instanceof Collection && count($orders) <= 5)
                ->where('topProducts', fn ($products) => $products instanceof Collection && count($products) <= 5)
                ->where('recentOrders.0', fn ($order) => isset(
                    $order['id'],
                    $order['customer_email'],
                    $order['total'],
                    $order['fulfillment_status'],
                    $order['created_at']
                ))
                ->where('topProducts.0', fn ($product) => isset(
                    $product['id'],
                    $product['title'],
                    $product['featured_media'],
                    $product['total_sold'],
                    $product['revenue']
                ))
                ->where('topProducts.0.total_sold', 5)
                ->where('topProducts.0.revenue', '250.0000')
        );
});

test('dashboard supports selected time period', function () {
    $customer = User::factory()->customer()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 0.00,
        'created_at' => now()->subDays(2),
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 200.00,
        'refund_total' => 0.00,
        'created_at' => now()->subDays(10),
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.dashboard', ['period' => '7d']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('period', '7d')
                ->where('from', now()->subDays(6)->format('Y-m-d'))
                ->where('to', now()->format('Y-m-d'))
                ->where('stats.totalRevenue', '100.0000')
                ->where('stats.totalOrders', 1)
                ->has('salesChart', 7)
        );
});

test('dashboard resolves every preset period', function (string $period, string $from, string $to) {
    $response = actingAsSuperAdmin()->get(route('admin.dashboard', ['period' => $period]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('period', $period)
                ->where('from', $from)
                ->where('to', $to)
        );
})->with([
    'today' => ['today', fn () => now()->format('Y-m-d'), fn () => now()->format('Y-m-d')],
    'yesterday' => ['yesterday', fn () => now()->subDay()->format('Y-m-d'), fn () => now()->subDay()->format('Y-m-d')],
    'last 7 days' => ['7d', fn () => now()->subDays(6)->format('Y-m-d'), fn () => now()->format('Y-m-d')],
    'last 30 days' => ['30d', fn () => now()->subDays(29)->format('Y-m-d'), fn () => now()->format('Y-m-d')],
    'this month' => ['this-month', fn () => now()->startOfMonth()->format('Y-m-d'), fn () => now()->format('Y-m-d')],
    'last month' => ['last-month', fn () => now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), fn () => now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')],
    'this quarter' => ['this-quarter', fn () => now()->startOfQuarter()->format('Y-m-d'), fn () => now()->format('Y-m-d')],
    'last quarter' => ['last-quarter', fn () => now()->subQuarterNoOverflow()->startOfQuarter()->format('Y-m-d'), fn () => now()->subQuarterNoOverflow()->endOfQuarter()->format('Y-m-d')],
    'this year' => ['this-year', fn () => now()->startOfYear()->format('Y-m-d'), fn () => now()->format('Y-m-d')],
]);

test('dashboard lifetime starts at the earliest order', function () {
    Order::factory()->create(['created_at' => now()->subYears(2)->startOfDay()]);

    actingAsSuperAdmin()->get(route('admin.dashboard', ['period' => 'lifetime']))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('period', 'lifetime')
                ->where('from', now()->subYears(2)->format('Y-m-d'))
                ->where('to', now()->format('Y-m-d'))
        );
});

test('dashboard exposes a range for every preset so the picker can preview them', function () {
    actingAsSuperAdmin()->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->has('periods', count(DatePeriod::cases()) - 1)
                ->where('periods.30d.from', now()->subDays(29)->format('Y-m-d'))
                ->where('periods.30d.to', now()->format('Y-m-d'))
                ->missing('periods.custom')
        );
});

test('dashboard supports a single-day custom period', function () {
    $customer = User::factory()->customer()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 0.00,
        'created_at' => now()->startOfDay()->addHours(9),
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.dashboard', [
        'period' => 'custom',
        'from' => now()->format('Y-m-d'),
        'to' => now()->format('Y-m-d'),
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->where('period', 'custom')
                ->where('from', now()->format('Y-m-d'))
                ->where('to', now()->format('Y-m-d'))
                ->where('stats.totalRevenue', '100.0000')
                ->where('stats.totalOrders', 1)
                ->has('salesChart', 1)
        );
});

test('dashboard accepts tomorrow to tolerate timezones ahead of the app timezone', function () {
    actingAsSuperAdmin()->get(route('admin.dashboard', [
        'period' => 'custom',
        'from' => now()->addDay()->format('Y-m-d'),
        'to' => now()->addDay()->format('Y-m-d'),
    ]))->assertValid(['from', 'to']);
});

test('dashboard rejects future dates beyond tomorrow', function () {
    actingAsSuperAdmin()->get(route('admin.dashboard', [
        'period' => 'custom',
        'from' => now()->addDays(2)->format('Y-m-d'),
        'to' => now()->addDays(3)->format('Y-m-d'),
    ]))->assertInvalid(['from']);
});

test('dashboard rejects an invalid period', function () {
    actingAsSuperAdmin()->get(route('admin.dashboard', ['period' => 'invalid']))
        ->assertInvalid(['period']);
});

test('dashboard requires from and to for a custom period', function () {
    actingAsSuperAdmin()->get(route('admin.dashboard', ['period' => 'custom']))
        ->assertInvalid(['from', 'to']);
});

test('dashboard rejects to before from', function () {
    actingAsSuperAdmin()->get(route('admin.dashboard', [
        'period' => 'custom',
        'from' => '2026-01-15',
        'to' => '2026-01-01',
    ]))->assertInvalid(['to']);
});

test('dashboard data integrity', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Customer);

    // Create paid orders with refunds
    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 100.00,
        'refund_total' => 10.00, // $10 refund
    ]);

    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => 200.00,
        'refund_total' => 0.00,
    ]);

    // Create unpaid order (should not affect revenue)
    Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Unpaid,
        'total' => 50.00,
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.dashboard'));

    $response->assertInertia(
        fn ($page) => $page
            ->where('stats.totalRevenue', '290.0000') // (100-10) + (200-0) = 290
            ->where('stats.totalOrders', 2) // only paid orders
            ->where('stats.totalCustomers', 1)
            ->where('stats.averageOrderValue', '145.0000') // 290 / 2 paid orders
    );
});

test('guests are redirected to the login page', function () {
    $response = get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('authenticated users can visit the dashboard', function () {
    $response = actingAsSuperAdmin()->get(route('admin.dashboard'));

    $response->assertOk();
});

test('requires dashboard.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.dashboard'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::DashboardView);

    // When dashboard.view is revoked but user has other permissions,
    // it redirects to the first accessible route instead of showing 403
    $response = actingAsAdmin()->get(route('admin.dashboard'));

    $response->assertRedirect();
});

test('redirects to first accessible route when user lacks dashboard.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    // Revoke dashboard.view but keep orders.view
    $role->revokePermissionTo(Permission::DashboardView);

    $response = actingAsAdmin()->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.orders.index'));
});

test('redirects a mail-only settings user to the settings page', function () {
    $user = userWithPermissions([Permission::SettingsMailConfigure]);

    $response = actingAs($user)->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.settings.index'));
});

test('throws authorization exception when user has no accessible routes', function () {
    $user = User::factory()->create();

    // User has no permissions at all

    $response = actingAs($user)->get(route('admin.dashboard'));

    $response->assertForbidden();
});
