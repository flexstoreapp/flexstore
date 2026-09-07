<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Storefront\TrackOrderRequest;
use App\Http\Resources\Api\V1\TrackedOrderResource;
use App\Models\Order;
use App\Queries\TrackedOrderQuery;
use Illuminate\Validation\ValidationException;

final readonly class TrackOrderController
{
    public function __invoke(TrackOrderRequest $request, TrackedOrderQuery $query): TrackedOrderResource
    {
        $order = Order::query()
            ->whereKey($request->safe()->integer('order_number'))
            ->whereRaw('lower(customer_email) = ?', [mb_strtolower($request->safe()->string('email')->value())])
            ->first();

        if (! $order instanceof Order) {
            throw ValidationException::withMessages([
                'order_number' => __('We couldn’t find an order matching that order number and email.'),
            ]);
        }

        return new TrackedOrderResource($query->execute($order->id));
    }
}
