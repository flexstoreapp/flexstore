<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use App\Models\Order;
use App\Utilities\LocalizedText;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Override;

/**
 * Wraps the period metadata plus the array shape produced by DashboardStatsQuery.
 */
final class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->resource;

        /** @var array<string, mixed> $stats */
        $stats = $data['stats'];

        return [
            'period' => $data['period'],
            'from' => $data['from'],
            'to' => $data['to'],
            'periods' => $data['periods'],
            'stats' => [
                'total_revenue' => $stats['totalRevenue'],
                'total_orders' => $stats['totalOrders'],
                'total_customers' => $stats['totalCustomers'],
                'revenue_change' => $stats['revenueChange'],
                'orders_change' => $stats['ordersChange'],
                'customers_change' => $stats['customersChange'],
                'average_order_value' => $stats['averageOrderValue'],
            ],
            'sales_chart' => $data['salesChart'],
            'recent_orders' => $this->recentOrders($data['recentOrders']),
            'top_products' => $this->topProducts($data['topProducts']),
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<array<string, mixed>>
     */
    private function recentOrders(Collection $orders): array
    {
        return array_values($orders->map(fn (Order $order): array => [
            'id' => $order->id,
            'created_at' => $order->created_at->toIso8601String(),
            'customer_email' => $order->customer_email,
            'customer_name' => $this->customerName($order),
            'fulfillment_status' => $order->fulfillment_status->value,
            'canceled_at' => $order->canceled_at?->toIso8601String(),
            'currency_code' => $order->currency_code,
            'total' => $order->total,
        ])->all());
    }

    private function customerName(Order $order): ?string
    {
        $address = $order->billingAddress;

        if ($address === null) {
            return null;
        }

        $name = mb_trim($address->first_name . ' ' . $address->last_name);

        return $name === '' ? null : $name;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @return list<array<string, mixed>>
     */
    private function topProducts(Collection $products): array
    {
        return array_values($products->map(fn (array $product): array => [
            'id' => $product['id'],
            'title' => LocalizedText::resolve($product['title']),
            'total_sold' => $product['total_sold'],
            'revenue' => $product['revenue'],
            'featured_media' => $product['featured_media'] instanceof Media
                ? new MediaResource($product['featured_media'])
                : null,
        ])->all());
    }
}
