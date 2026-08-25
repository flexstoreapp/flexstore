<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Configs\OrderFilterConfig;
use App\Filters\FilterManager;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class OrderListQuery
{
    /**
     * @param  FilterManager<Order>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Order>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->filterManager->setCriteria(OrderFilterConfig::getCriteria($filters['direction'] ?? null));

        return Order::query()
            ->select([
                'id',
                'customer_email',
                'total',
                'currency_code',
                'exchange_rate',
                'payment_status',
                'fulfillment_status',
                'canceled_at',
                'created_at',
            ])
            ->withSum('items', 'quantity')
            ->with('billingAddress:order_id,type,first_name,last_name')
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString();
    }
}
