<?php

declare(strict_types=1);

namespace App\Filters\Strategies;

use App\Enums\OrderAddressType;
use App\Filters\Contracts\ColumnSortStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * @template TModel of \App\Models\Order
 *
 * @implements ColumnSortStrategy<TModel>
 */
final readonly class CustomerNameColumnSortStrategy implements ColumnSortStrategy
{
    public function apply(Builder $builder, string $direction): Builder
    {
        $orderByRaw = match (DB::getDriverName()) {
            'sqlite', 'pgsql' => match ($direction) {
                'desc' => "(COALESCE(order_addresses.first_name, '') || ' ' || COALESCE(order_addresses.last_name, '')) desc",
                default => "(COALESCE(order_addresses.first_name, '') || ' ' || COALESCE(order_addresses.last_name, '')) asc",
            },
            default => match ($direction) {
                'desc' => "CONCAT(COALESCE(order_addresses.first_name, ''), ' ', COALESCE(order_addresses.last_name, '')) desc",
                default => "CONCAT(COALESCE(order_addresses.first_name, ''), ' ', COALESCE(order_addresses.last_name, '')) asc",
            },
        };

        return $builder
            ->select('orders.*')
            ->leftJoin('order_addresses', function (JoinClause $join): void {
                $join->on('order_addresses.order_id', '=', 'orders.id')
                    ->where('order_addresses.type', '=', OrderAddressType::Billing->value);
            })
            ->orderByRaw($orderByRaw);
    }
}
