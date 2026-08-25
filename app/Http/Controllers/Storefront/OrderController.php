<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\DisplayTaxTotals;
use App\Http\Requests\Storefront\ShowAccountOrdersRequest;
use App\Models\Setting;
use App\Models\User;
use App\Queries\CustomerOrderListQuery;
use App\Queries\CustomerOrderQuery;
use App\Queries\OrderItemBreakdownQuery;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrderController
{
    public function index(
        ShowAccountOrdersRequest $request,
        #[CurrentUser] User $user,
        CustomerOrderListQuery $query,
    ): Response {
        $status = $request->safe()->string('status')->value();

        StorefrontHead::page(__('Orders'));

        return Inertia::render('storefront/account/orders/list', [
            'orders' => $query->execute($user, $status !== '' ? $status : null),
            'status' => $status !== '' ? $status : null,
        ]);
    }

    public function show(
        int $orderId,
        #[CurrentUser] User $user,
        CustomerOrderQuery $customerOrderQuery,
        OrderItemBreakdownQuery $itemBreakdownQuery,
    ): Response {
        $order = $customerOrderQuery->execute($orderId, $user);

        StorefrontHead::page(__('Order :number', ['number' => '#' . $order->id]));

        return Inertia::render('storefront/account/orders/show', [
            'order' => $order,
            'displayTaxTotals' => (DisplayTaxTotals::tryFrom((string) Setting::getValue('display_tax_totals'))
                ?? DisplayTaxTotals::Single)->value,
            'itemBreakdown' => $itemBreakdownQuery->execute($order),
        ]);
    }
}
