<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderRefundItem;
use App\Models\Product;
use App\Models\User;
use App\Utilities\MoneyFormatter;
use App\Utilities\TimeBucket;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class DashboardStatsQuery
{
    /**
     * @return array{
     *     stats: array{
     *         totalRevenue: string,
     *         totalOrders: int,
     *         totalCustomers: int,
     *         revenueChange: float,
     *         ordersChange: float,
     *         customersChange: float,
     *         averageOrderValue: string,
     *     },
     *     salesChart: list<array<string, mixed>>,
     *     recentOrders: Collection<int, Order>,
     *     topProducts: Collection<int, array{
     *         id: int,
     *         title: array<string, string>,
     *         featured_media: Media|null,
     *         total_sold: int,
     *         revenue: string,
     *     }>,
     * }
     */
    public function execute(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from ??= Date::now()->subDays(29)->startOfDay();
        $to ??= Date::now()->endOfDay();

        return [
            'stats' => $this->getStats($from, $to),
            'salesChart' => $this->getSalesChart($from, $to),
            'recentOrders' => $this->getRecentOrders(),
            'topProducts' => $this->getTopProducts(),
        ];
    }

    /**
     * @return array{
     *     totalRevenue: string,
     *     totalOrders: int,
     *     totalCustomers: int,
     *     revenueChange: float,
     *     ordersChange: float,
     *     customersChange: float,
     *     averageOrderValue: string,
     * }
     */
    private function getStats(CarbonInterface $from, CarbonInterface $to): array
    {
        [$previousFrom, $previousTo] = $this->previousPeriod($from, $to);

        $currentOrders = $this->getOrderStats($from, $to);
        $previousOrders = $this->getOrderStats($previousFrom, $previousTo);
        $totalCustomers = $this->getCustomerCount($from, $to);
        $previousCustomers = $this->getCustomerCount($previousFrom, $previousTo);

        $averageOrderValue = $currentOrders['totalOrders'] > 0
            ? BigDecimal::of($currentOrders['totalRevenue'])->dividedBy($currentOrders['totalOrders'], 4, RoundingMode::HalfUp)->toString()
            : '0.0000';

        return [
            'totalRevenue' => BigDecimal::of($currentOrders['totalRevenue'])->toScale(4, RoundingMode::HalfUp)->toString(),
            'totalOrders' => $currentOrders['totalOrders'],
            'totalCustomers' => $totalCustomers,
            'revenueChange' => $this->calculatePercentageChange(
                (float) $previousOrders['totalRevenue'],
                (float) $currentOrders['totalRevenue'],
            ),
            'ordersChange' => $this->calculatePercentageChange(
                $previousOrders['totalOrders'],
                $currentOrders['totalOrders'],
            ),
            'customersChange' => $this->calculatePercentageChange(
                $previousCustomers,
                $totalCustomers,
            ),
            'averageOrderValue' => $averageOrderValue,
        ];
    }

    /**
     * @return array{totalOrders: int, totalRevenue: string}
     */
    private function getOrderStats(CarbonInterface $from, CarbonInterface $to): array
    {
        $stats = Order::query()
            ->whereIn('payment_status', [PaymentStatus::Paid, PaymentStatus::PartiallyPaid, PaymentStatus::PartiallyRefunded])
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COALESCE(SUM(net_paid_total / exchange_rate), 0) as total_revenue')
            ->first();
        assert($stats instanceof Order);

        return [
            'totalOrders' => (int) $stats->total_orders, // @phpstan-ignore property.notFound
            'totalRevenue' => (string) $stats->total_revenue, // @phpstan-ignore property.notFound
        ];
    }

    private function getCustomerCount(CarbonInterface $from, CarbonInterface $to): int
    {
        return User::query()
            ->whereHas('orders')
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', Role::Customer))
            ->whereBetween('users.created_at', [$from, $to])
            ->count();
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function previousPeriod(CarbonInterface $from, CarbonInterface $to): array
    {
        $days = $from->diffInDays($to);

        return [
            $from->copy()->subDays($days + 1)->startOfDay(),
            $from->copy()->subDay()->endOfDay(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getSalesChart(CarbonInterface $from, CarbonInterface $to): array
    {
        $paidValues = [PaymentStatus::Paid->value, PaymentStatus::PartiallyPaid->value, PaymentStatus::PartiallyRefunded->value];

        $orders = Order::query()
            ->whereIn('payment_status', [PaymentStatus::Paid, PaymentStatus::PartiallyPaid, PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded])
            ->whereBetween('created_at', [$from, $to])
            ->select([TimeBucket::dateGroupExpression($from, $to)])
            ->selectRaw('SUM(CASE WHEN payment_status IN (?, ?, ?) THEN net_paid_total / exchange_rate ELSE 0 END) as net_sales', $paidValues)
            ->groupBy('date_group')
            ->orderBy('date_group')
            ->get()
            ->keyBy('date_group');

        return TimeBucket::fill($from, $to, $orders, fn (string $date, ?Order $day): array => [
            'date' => $date,
            'net_sales' => MoneyFormatter::toDecimal($day instanceof Order ? (string) $day->getAttribute('net_sales') : '0'),
        ]);
    }

    /**
     * @return Collection<int, Order>
     */
    private function getRecentOrders(): Collection
    {
        return Order::query()
            ->with('billingAddress:order_id,type,first_name,last_name')
            ->latest()
            ->limit(5)
            ->get([
                'id',
                'customer_email',
                'total',
                'currency_code',
                'fulfillment_status',
                'canceled_at',
                'created_at',
            ]);
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     title: array<string, string>,
     *     featured_media: Media|null,
     *     total_sold: int,
     *     revenue: string
     * }>
     */
    private function getTopProducts(): Collection
    {
        return Product::query() // @phpstan-ignore return.type
            ->select([
                'products.id',
                'products.title',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM((order_items.total_price - COALESCE(ri.refund_amount, 0)) / orders.exchange_rate) as revenue'),
            ])
            ->withFeaturedMedia()
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoinSub(OrderRefundItem::completedTotalsByOrderItem(), 'ri', 'order_items.id', '=', 'ri.order_item_id')
            ->whereIn('orders.payment_status', [PaymentStatus::Paid, PaymentStatus::PartiallyPaid, PaymentStatus::PartiallyRefunded])
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'title' => $product->getTranslations('title'),
                'featured_media' => $product->featured_media,
                'total_sold' => (int) $product->total_sold,
                'revenue' => BigDecimal::of((string) ($product->revenue ?? '0'))->toScale(4, RoundingMode::HalfUp)->toString(),
            ]);
    }

    private function calculatePercentageChange(float $oldValue, float $newValue): float
    {
        if ($oldValue === 0.0) {
            return $newValue > 0 ? 100.0 : 0.0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }
}
