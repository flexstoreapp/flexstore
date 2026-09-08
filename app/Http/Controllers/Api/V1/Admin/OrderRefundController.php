<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\ProcessRefundAction;
use App\Exceptions\GatewayRefundFailedException;
use App\Http\Requests\Admin\StoreRefundRequest;
use App\Http\Resources\Api\V1\Admin\OrderRefundResource;
use App\Http\Resources\Api\V1\Admin\RefundableOrderResource;
use App\Models\Order;
use App\Models\User;
use App\Payment\PaymentManager;
use App\Queries\RefundableOrderDataQuery;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Validation\ValidationException;

final readonly class OrderRefundController
{
    public function __construct(
        private PaymentManager $paymentManager,
    ) {
    }

    public function show(Order $order, RefundableOrderDataQuery $query): RefundableOrderResource
    {
        abort_unless($order->is_refundable, 410, __('This order cannot be refunded.'));

        return new RefundableOrderResource([
            ...$query->execute($order),
            'supports_gateway_refund' => $this->supportsGatewayRefund($order),
        ]);
    }

    public function store(
        StoreRefundRequest $request,
        #[CurrentUser] User $user,
        Order $order,
        ProcessRefundAction $action,
    ): OrderRefundResource {
        try {
            $refund = $action->handle($user, $order, $request->toDto());
        } catch (GatewayRefundFailedException $e) {
            throw ValidationException::withMessages(['gateway' => $e->getMessage()]);
        }

        return new OrderRefundResource($refund->load('items'));
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
