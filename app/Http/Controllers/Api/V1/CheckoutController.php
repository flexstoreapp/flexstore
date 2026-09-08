<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ResolveVisitorCartAction;
use App\Actions\StoreCheckoutSessionAction;
use App\Enums\PaymentStatus;
use App\Http\Requests\Api\V1\StoreCheckoutRequest;
use App\Utilities\ApiCartToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final readonly class CheckoutController
{
    public function store(
        StoreCheckoutRequest $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        StoreCheckoutSessionAction $storeCheckoutSession,
    ): JsonResponse {
        $result = $storeCheckoutSession->handle(
            $request->toDto(),
            $resolveVisitorCart->handle(ApiCartToken::from($request), $request->user())->id,
            $request->user(),
        );

        $payment = $result->paymentSession;

        if ($payment->status === PaymentStatus::Failed) {
            return response()->json([
                'message' => $payment->failureReason ?? __('Payment failed. Please try again.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $session = $result->checkoutSession;

        return response()->json([
            'checkout_session_id' => $session->id,
            'payment_status' => $payment->status->value,
            'redirect_url' => $payment->redirectUrl,
            'gateway_reference' => $payment->gatewayReference,
            'payload' => $payment->payload,
            'cancel_url' => URL::temporarySignedRoute(
                'checkout.cancel.store',
                now()->addDay(),
                ['session' => $session->id],
            ),
        ], Response::HTTP_CREATED);
    }
}
