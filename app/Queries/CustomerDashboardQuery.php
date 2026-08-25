<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\FulfillmentStatus;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class CustomerDashboardQuery
{
    /**
     * @return array{
     *     stats: array{total: int, unfulfilled: int, fulfilled: int, downloads: int},
     *     recentOrders: Collection<int, Order>,
     *     defaultAddress: CustomerAddress|null,
     * }
     */
    public function execute(User $user): array
    {
        $counts = Order::query()
            ->where('customer_id', $user->id)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when canceled_at is null and fulfillment_status = ? then 1 else 0 end) as fulfilled', [FulfillmentStatus::Fulfilled->value])
            ->selectRaw('sum(case when canceled_at is null and fulfillment_status <> ? then 1 else 0 end) as unfulfilled', [FulfillmentStatus::Fulfilled->value])
            ->first();

        $downloads = OrderItemDownload::query()
            ->whereHas('order', fn (Builder $query): Builder => $query->where('customer_id', $user->id))
            ->count();

        $recentOrders = Order::query()
            ->where('customer_id', $user->id)
            ->select(['id', 'created_at', 'fulfillment_status', 'payment_status', 'canceled_at', 'total', 'currency_code'])
            ->withSum('items', 'quantity')
            ->latest()
            ->limit(3)
            ->get();

        $defaultAddress = $user->addresses()
            ->where('is_default', true)
            ->select([
                'id', 'user_id', 'is_default',
                'first_name', 'last_name',
                'address_line_1', 'address_line_2',
                'city', 'state', 'postal_code', 'country_code', 'phone',
            ])
            ->first();

        return [
            'stats' => [
                'total' => (int) ($counts->total ?? 0),
                'unfulfilled' => (int) ($counts->unfulfilled ?? 0),
                'fulfilled' => (int) ($counts->fulfilled ?? 0),
                'downloads' => $downloads,
            ],
            'recentOrders' => $recentOrders,
            'defaultAddress' => $defaultAddress,
        ];
    }
}
