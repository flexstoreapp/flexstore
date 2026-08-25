<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ProcessRefundAction;
use App\Exceptions\GatewayRefundFailedException;
use App\Http\Requests\Admin\StoreRefundRequest;
use App\Models\Media;
use App\Models\Order;
use App\Models\User;
use App\Payment\PaymentManager;
use App\Queries\RefundableOrderDataQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrderRefundController
{
    public function __construct(
        private PaymentManager $paymentManager,
    ) {
    }

    public function create(Order $order, RefundableOrderDataQuery $query): Response
    {
        abort_unless($order->is_refundable, 410, __('This order cannot be refunded.'));

        $order->load([
            'items.media:' . Media::displaySelect(),
        ]);

        $refundData = $query->execute($order);

        return Inertia::render('admin/orders/refund', [
            'order' => $order,
            'refundableQuantities' => $refundData['refundable_quantities'],
            'refundableShippingAmount' => $refundData['refundable_shipping_amount'],
            'maxRefundableAmount' => $refundData['max_refundable_amount'],
            'supportsGatewayRefund' => $this->supportsGatewayRefund($order),
        ]);
    }

    public function store(
        StoreRefundRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        ProcessRefundAction $action,
    ): RedirectResponse {
        try {
            $action->handle($user, $order, $request->toDto());
        } catch (GatewayRefundFailedException $e) {
            return back()->withErrors(['gateway' => $e->getMessage()]);
        }

        return to_route('admin.orders.show', $order);
    }

    private function supportsGatewayRefund(Order $order): bool
    {
        $gateway = $order->paymentGateway;

        if (! $gateway) {
            return false;
        }

        $driver = $this->paymentManager->driver($gateway);

        return ! $driver->isManual() && $driver->supportsRefunds();
    }
}
