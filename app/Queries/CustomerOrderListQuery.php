<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\FulfillmentStatus;
use App\Models\Media;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class CustomerOrderListQuery
{
    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function execute(User $user, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        return Order::query()
            ->where('customer_id', $user->id)
            ->select(['id', 'created_at', 'fulfillment_status', 'payment_status', 'canceled_at', 'total', 'currency_code'])
            ->withCount('items')
            ->with([
                'items' => fn (Relation $query): Relation => $query->select([
                    'id', 'order_id', 'media_id', 'product_id',
                    'product_title', 'variant_title', 'quantity', 'total_price',
                ])->orderBy('id')->limit(2),
                'items.media:' . Media::displaySelect(),
                'items.product:id,url_handle,is_active',
            ])
            ->when($status === 'cancelled', fn (Builder $query): Builder => $query->whereNotNull('canceled_at'))
            ->when(
                $status !== null && $status !== 'cancelled' && FulfillmentStatus::tryFrom($status) !== null,
                fn (Builder $query): Builder => $query->whereNull('canceled_at')->where('fulfillment_status', $status),
            )
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
