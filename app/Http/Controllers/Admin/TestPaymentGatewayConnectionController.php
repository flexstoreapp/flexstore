<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentGatewayDriver;
use App\Http\Requests\Admin\TestPaymentGatewayConnectionRequest;
use App\Models\PaymentGateway;
use App\Payment\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Throwable;

final readonly class TestPaymentGatewayConnectionController
{
    public function __invoke(TestPaymentGatewayConnectionRequest $request, PaymentManager $paymentManager): RedirectResponse
    {
        $gateway = new PaymentGateway([
            'driver' => $request->safe()->enum('driver', PaymentGatewayDriver::class),
            'credentials' => $request->safe()->array('credentials'),
        ]);

        try {
            $connected = $paymentManager->driver($gateway)->testConnection();
        } catch (Throwable) {
            $connected = false;
        }

        if (! $connected) {
            return back()->withErrors([
                'connection' => __('Could not connect to the payment gateway. Please check your credentials.'),
            ]);
        }

        return back();
    }
}
