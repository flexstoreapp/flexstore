<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ApplyPendingCouponAction;
use App\Actions\RecalculateCartTotalsAction;
use App\Actions\ResolveVisitorCartAction;
use App\Actions\StoreCheckoutSessionAction;
use App\Actions\SyncCartItemPricesAction;
use App\Address\AddressFieldRules;
use App\Enums\DisplayTaxTotals;
use App\Enums\PaymentStatus;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Models\Setting;
use App\Queries\CustomerAddressListQuery;
use App\Queries\PendingCheckoutDraftByCartQuery;
use App\Utilities\CartCookie;
use App\Utilities\StorefrontHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final readonly class CheckoutController
{
    public function create(
        Request $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        SyncCartItemPricesAction $syncCartItemPrices,
        RecalculateCartTotalsAction $recalculateCartTotals,
        ApplyPendingCouponAction $applyPendingCoupon,
        CustomerAddressListQuery $customerAddresses,
        PendingCheckoutDraftByCartQuery $pendingDraft,
    ): Response {
        $user = $request->user();
        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        if ($cart->items->isNotEmpty()) {
            $syncCartItemPrices->handle($cart);
            $recalculateCartTotals->handle($cart);

            $pendingCoupon = $request->session()->get('pending_coupon');

            if (is_string($pendingCoupon) && $applyPendingCoupon->handle($cart, $pendingCoupon, $user)) {
                $request->session()->forget('pending_coupon');
            }
        }

        $savedAddresses = $user !== null ? $customerAddresses->execute($user) : collect();
        $recoveredCheckout = $pendingDraft->execute($cart->id);
        $storeCountryCode = (string) Setting::getValue('store_country_code', '');

        $initialCountry = $recoveredCheckout['shipping_address']['country_code']
            ?? $savedAddresses->firstWhere('is_default', true)->country_code
            ?? $savedAddresses->first()->country_code
            ?? $storeCountryCode;

        StorefrontHead::page(__('Checkout'));

        return Inertia::render('storefront/checkout/create', [
            'savedAddresses' => $savedAddresses,
            'storeCountryCode' => $storeCountryCode,
            'pricesIncludeTax' => (bool) Setting::getValue('prices_include_tax'),
            'displayTaxTotals' => (DisplayTaxTotals::tryFrom((string) Setting::getValue('display_tax_totals'))
                ?? DisplayTaxTotals::Single)->value,
            'addressFieldRules' => AddressFieldRules::for((string) $initialCountry),
            'recoveredCheckout' => $recoveredCheckout,
        ]);
    }

    public function store(
        StoreCheckoutRequest $request,
        ResolveVisitorCartAction $resolveVisitorCart,
        StoreCheckoutSessionAction $storeCheckoutSession,
    ): SymfonyResponse {
        $checkoutData = $storeCheckoutSession->handle(
            $request->toDto(),
            $resolveVisitorCart->handle(CartCookie::from($request), $request->user())->id,
            $request->user(),
        );
        $paymentSession = $checkoutData->paymentSession;

        if ($paymentSession->status === PaymentStatus::Failed) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $paymentSession->failureReason], 422);
            }

            throw ValidationException::withMessages([
                'payment' => $paymentSession->failureReason ?? __('Payment failed. Please try again.'),
            ]);
        }

        $session = $checkoutData->checkoutSession;
        $cancelUrl = URL::temporarySignedRoute('checkout.cancel.store', now()->addDay(), ['session' => $session->id]);

        if ($request->expectsJson() && isset($paymentSession->payload['return_url'])) {
            return response()->json([
                ...$paymentSession->payload,
                'cancel_url' => $cancelUrl,
            ]);
        }

        $redirectUrl = $paymentSession->redirectUrl;
        $host = parse_url($redirectUrl, PHP_URL_HOST);
        $isExternal = $host !== null && $host !== $request->getHost();

        if ($isExternal) {
            return Inertia::location($redirectUrl);
        }

        return redirect()->to($redirectUrl);
    }
}
