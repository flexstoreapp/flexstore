<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Queries\PaymentGatewayListQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PaymentSettingController
{
    public function show(PaymentGatewayListQuery $query): Response
    {
        return Inertia::render('admin/settings/payment', [
            'paymentGateways' => $query->execute(),
        ]);
    }
}
