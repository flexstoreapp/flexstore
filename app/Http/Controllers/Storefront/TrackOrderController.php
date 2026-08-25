<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Requests\Storefront\TrackOrderRequest;
use App\Models\Order;
use App\Queries\TrackedOrderQuery;
use App\Utilities\StorefrontHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TrackOrderController
{
    public function create(): Response
    {
        StorefrontHead::page(__('Track order'));

        return Inertia::render('storefront/orders/track', [
            'order' => null,
        ]);
    }

    public function store(TrackOrderRequest $request): RedirectResponse
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

        return redirect(URL::signedRoute('orders.track.show', ['order' => $order->id]));
    }

    public function show(Request $request, TrackedOrderQuery $trackedOrder, int $order): Response
    {
        abort_unless($request->hasValidSignature(), 403);

        StorefrontHead::page(__('Track order'));

        return Inertia::render('storefront/orders/track', [
            'order' => $trackedOrder->execute($order),
        ]);
    }
}
