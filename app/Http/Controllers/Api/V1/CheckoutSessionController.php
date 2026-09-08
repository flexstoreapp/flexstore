<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelCheckoutSessionAction;
use App\Actions\ConfirmCheckoutPaymentAction;
use App\Enums\CheckoutSessionStatus;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Queries\CustomerOrderQuery;
use App\Utilities\ApiCartToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CheckoutSessionController
{
    public function show(
        Request $request,
        CheckoutSession $session,
        ConfirmCheckoutPaymentAction $confirmPayment,
        CustomerOrderQuery $customerOrderQuery,
    ): JsonResponse {
        $this->authorizeVisitor($request, $session);

        if ($session->status === CheckoutSessionStatus::Pending) {
            $confirmPayment->handle($session);
            $session->refresh();
        }

        $order = $session->order_id === null ? null : Order::query()->find($session->order_id);
        $user = $request->user();

        return response()->json([
            'checkout_session_id' => $session->id,
            'status' => $session->status->value,
            'order' => $order instanceof Order && $user !== null && $order->customer_id === $user->id
                ? new OrderResource($customerOrderQuery->execute($order->id, $user))
                : null,
            'order_id' => $order?->id,
        ]);
    }

    public function destroy(
        Request $request,
        CheckoutSession $session,
        CancelCheckoutSessionAction $action,
    ): JsonResponse {
        $this->authorizeVisitor($request, $session);

        $action->handle($session);

        return response()->json(['message' => __('Checkout canceled.')]);
    }

    private function authorizeVisitor(Request $request, CheckoutSession $session): void
    {
        abort_unless($session->belongsToVisitor(ApiCartToken::from($request), $request->user()), 403);
    }
}
