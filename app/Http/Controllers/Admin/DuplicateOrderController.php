<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\DisplayTaxTotals;
use App\Models\Order;
use App\Models\Setting;
use App\Queries\ActivePaymentGatewayListQuery;
use App\Queries\DuplicateOrderChangeQuery;
use App\Queries\DuplicateOrderSourceQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DuplicateOrderController
{
    public function create(
        Order $order,
        DuplicateOrderSourceQuery $duplicateOrderSourceQuery,
        DuplicateOrderChangeQuery $duplicateOrderChangeQuery,
        ActivePaymentGatewayListQuery $paymentGatewayQuery,
    ): Response {
        return Inertia::render('admin/orders/create', [
            'sourceOrder' => $duplicateOrderSourceQuery->execute($order),
            'sourceOrderChanges' => $duplicateOrderChangeQuery->execute($order),
            'paymentGateways' => $paymentGatewayQuery->execute(),
            'displayTaxTotals' => DisplayTaxTotals::from(Setting::getValue('display_tax_totals')),
        ]);
    }
}
