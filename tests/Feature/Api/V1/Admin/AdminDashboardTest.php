<?php

declare(strict_types=1);

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Requests\Admin\ShowDashboardRequest;
use App\Http\Resources\Api\V1\Admin\DashboardResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

use function Pest\Laravel\getJson;

covers(DashboardController::class, DashboardResource::class, ShowDashboardRequest::class);

uses()->group('api', 'admin-api');

test('the dashboard returns metrics for the default period', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    $product = Product::factory()->create(['title' => ['en' => 'Sample Product']]);
    $order = Order::factory()->create([
        'customer_email' => 'buyer@example.com',
        'total' => '100.0000',
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'total_price' => '100.0000',
    ]);

    getJson('/api/v1/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('period', '30d')
        ->assertJsonPath('from', now()->subDays(29)->format('Y-m-d'))
        ->assertJsonPath('to', now()->format('Y-m-d'))
        ->assertJsonPath('stats.total_orders', 1)
        ->assertJsonPath('recent_orders.0.id', $order->id)
        ->assertJsonPath('top_products.0.title', 'Sample Product')
        ->assertJsonPath('top_products.0.total_sold', 3)
        ->assertJsonStructure([
            'period',
            'from',
            'to',
            'periods',
            'stats' => [
                'total_revenue',
                'total_orders',
                'total_customers',
                'revenue_change',
                'orders_change',
                'customers_change',
                'average_order_value',
            ],
            'sales_chart',
            'recent_orders' => [['id', 'created_at', 'customer_email', 'customer_name', 'fulfillment_status', 'currency_code', 'total']],
            'top_products' => [['id', 'title', 'total_sold', 'revenue', 'featured_media']],
        ]);
});

test('the dashboard accepts a custom period', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson('/api/v1/admin/dashboard?period=custom&from=' . now()->subDays(3)->format('Y-m-d') . '&to=' . now()->format('Y-m-d'))
        ->assertOk()
        ->assertJsonPath('period', 'custom')
        ->assertJsonPath('from', now()->subDays(3)->format('Y-m-d'));
});

test('a custom period without dates is rejected', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson('/api/v1/admin/dashboard?period=custom')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['from', 'to']);
});

test('the dashboard requires the dashboard view permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    getJson('/api/v1/admin/dashboard')->assertForbidden();
});

test('the dashboard requires authentication', function (): void {
    getJson('/api/v1/admin/dashboard')->assertUnauthorized();
});
