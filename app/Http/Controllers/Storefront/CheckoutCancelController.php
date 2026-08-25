<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\CancelCheckoutSessionAction;
use App\Http\Requests\Storefront\ShowCheckoutCancelRequest;
use App\Models\CheckoutSession;
use App\Utilities\SignedRequest;
use App\Utilities\StorefrontHead;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CheckoutCancelController
{
    public function __construct(
        private CancelCheckoutSessionAction $cancelCheckoutSessionAction,
    ) {
    }

    public function show(ShowCheckoutCancelRequest $request): Response
    {
        abort_unless(SignedRequest::hasValidSignature($request, ['session']), 403);

        $session = CheckoutSession::query()->findOrFail($request->safe()->string('session')->value());

        $this->cancelCheckoutSessionAction->handle($session);
        StorefrontHead::page(__('Checkout canceled'));

        return Inertia::render('storefront/checkout/cancel');
    }

    public function store(CheckoutSession $session): JsonResponse
    {
        $this->cancelCheckoutSessionAction->handle($session);

        return response()->json(['canceled' => true]);
    }
}
