<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ConfirmCheckoutPaymentAction;
use App\Enums\CheckoutSessionStatus;
use App\Http\Requests\Storefront\ShowCheckoutSuccessRequest;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Queries\CheckoutSuccessPageQuery;
use App\Utilities\SignedRequest;
use App\Utilities\StorefrontHead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CheckoutSuccessController
{
    public function __construct(
        private ConfirmCheckoutPaymentAction $confirmCheckoutPaymentAction,
        private CheckoutSuccessPageQuery $successPage,
    ) {
    }

    public function show(ShowCheckoutSuccessRequest $request): Response|RedirectResponse
    {
        abort_unless(SignedRequest::hasValidSignature($request, ['session']), 403);

        $session = CheckoutSession::query()->with('paymentGateway')->findOrFail($request->safe()->string('session')->value());

        if ($session->status === CheckoutSessionStatus::Completed && $session->order_id !== null) {
            return $this->renderSuccess($session, Order::query()->findOrFail($session->order_id), $request);
        }

        if ($session->status === CheckoutSessionStatus::Pending) {
            $order = $this->confirmCheckoutPaymentAction->handle($session);

            if ($order instanceof Order) {
                return $this->renderSuccess($session, $order, $request);
            }
        }

        return redirect()->to(
            URL::temporarySignedRoute('checkout.cancel', now()->addDay(), ['session' => $session->id])
        );
    }

    private function renderSuccess(CheckoutSession $session, Order $order, ShowCheckoutSuccessRequest $request): Response
    {
        $cookieCartId = $request->cookie('cart_id');
        $isBuyer = $session->belongsToVisitor(is_string($cookieCartId) ? $cookieCartId : null, $request->user());

        StorefrontHead::page($isBuyer ? __('Order confirmed') : __('Payment complete'));

        return Inertia::render('storefront/checkout/success', $this->successPage->execute(
            $order,
            $isBuyer,
        ));
    }
}
