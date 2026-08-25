<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\StorePaymentGatewayAction;
use App\Actions\UpdatePaymentGatewayAction;
use App\Http\Requests\Admin\StorePaymentGatewayRequest;
use App\Http\Requests\Admin\UpdatePaymentGatewayRequest;
use App\Models\PaymentGateway;
use Illuminate\Http\RedirectResponse;

final readonly class PaymentGatewayController
{
    public function store(StorePaymentGatewayRequest $request, StorePaymentGatewayAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back();
    }

    public function update(PaymentGateway $gateway, UpdatePaymentGatewayRequest $request, UpdatePaymentGatewayAction $action): RedirectResponse
    {
        $action->handle($gateway, $request->toDto());

        return back();
    }
}
