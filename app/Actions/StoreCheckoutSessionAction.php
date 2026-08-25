<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\Address;
use App\DTOs\AddressLocation;
use App\DTOs\CheckoutInitiationResult;
use App\DTOs\CheckoutSessionTotals;
use App\DTOs\CouponValidationResult;
use App\DTOs\HydratedOrderItem;
use App\DTOs\OrderItemsSummary;
use App\DTOs\StoreCheckoutInput;
use App\DTOs\StoreCustomerAddressInput;
use App\DTOs\TaxCalculationInput;
use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use App\Enums\PaymentStatus;
use App\Enums\TaxBasedOn;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Currency;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Models\User;
use App\Payment\DTOs\SessionResult;
use App\Queries\EligiblePaymentOptionsQuery;
use App\Queries\EligibleShippingOptionsQuery;
use App\Utilities\CartItemValidator;
use App\Utilities\CouponValidator;
use App\Utilities\CurrencyConverter;
use App\Utilities\OrderTaxCalculator;
use App\Utilities\OrderUtility;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

final readonly class StoreCheckoutSessionAction
{
    public function __construct(
        private EligibleShippingOptionsQuery $eligibleShippingOptionsQuery,
        private EligiblePaymentOptionsQuery $eligiblePaymentOptionsQuery,
        private ResolveCartAction $resolveCartAction,
        private CartItemValidator $cartItemValidator,
        private SyncCartItemPricesAction $syncCartItemPricesAction,
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
        private CancelCheckoutSessionAction $cancelCheckoutSessionAction,
        private ReleaseStockReservationsAction $releaseStockReservationsAction,
        private ReserveStockAction $reserveStockAction,
        private StoreCustomerAddressAction $storeCustomerAddressAction,
        private OrderUtility $orderUtility,
        private CouponValidator $couponValidator,
        private OrderTaxCalculator $orderTaxCalculator,
        private CurrencyConverter $currencyConverter,
        private CreateCheckoutPaymentSessionAction $createCheckoutPaymentSession,
    ) {
    }

    public function handle(StoreCheckoutInput $input, ?string $cartId = null, ?User $customer = null): CheckoutInitiationResult
    {
        $cart = $this->resolveCartAction->handle($cartId, $customer);

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('Your cart is empty'),
            ]);
        }

        $this->syncCartPrices($cart);

        [$checkoutSession, $cart] = DB::transaction(
            fn (): array => $this->persistCheckoutSession($cart, $input, $customer),
        );

        if ($checkoutSession->payment_gateway_id === null) {
            return new CheckoutInitiationResult(
                checkoutSession: $checkoutSession,
                paymentSession: new SessionResult(
                    status: PaymentStatus::Unpaid,
                    redirectUrl: URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $checkoutSession->id]),
                    payload: ['message' => __('Order placed')],
                ),
                cart: $cart,
            );
        }

        return $this->createCheckoutPaymentSession->handle($checkoutSession, $cart, $input->cardToken);
    }

    private function syncCartPrices(Cart $cart): void
    {
        $subtotalBefore = $cart->subtotal;

        $cart = $this->syncCartItemPricesAction->handle($cart);
        $cart = $this->recalculateCartTotalsAction->handle($cart);

        if (BigDecimal::of($cart->subtotal)->isGreaterThan($subtotalBefore)) {
            throw ValidationException::withMessages([
                'cart' => __('Some prices have changed since you last viewed your cart. Please review the updated prices.'),
            ]);
        }
    }

    /**
     * @return array{0: CheckoutSession, 1: Cart}
     */
    private function persistCheckoutSession(Cart $cart, StoreCheckoutInput $input, ?User $customer): array
    {
        $cart = Cart::query()
            ->with('items')
            ->whereKey($cart->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('Your cart is empty'),
            ]);
        }

        $existingSession = $this->cleanupPendingSessions($cart->id);

        $this->cartItemValidator->validate($cart->items);

        $requiresShipping = $cart->requiresShipping();

        $paymentGateway = $input->paymentGatewayId !== null
            ? $this->resolvePaymentGateway($input->paymentGatewayId)
            : null;
        $shippingRate = $requiresShipping && $input->shippingRateId !== null
            ? $this->resolveShippingRate($input->shippingRateId)
            : null;
        $shippingAddress = $requiresShipping && $input->shippingAddress instanceof Address
            ? $input->shippingAddress->toArray()
            : null;
        $billingAddress = $this->resolveBillingAddress($input);
        $shippingLocation = $shippingAddress !== null ? AddressLocation::fromArray($shippingAddress) : null;
        $billingLocation = $billingAddress !== [] ? AddressLocation::fromArray($billingAddress) : null;

        $hydratedItems = $this->orderUtility->hydrateItems(
            $cart->items->map(fn (CartItem $item): array => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'variant_options' => $item->variant_options ?? [],
            ])->all(),
        );

        $orderItemsSummary = OrderItemsSummary::fromHydratedItems($hydratedItems);

        if ($shippingRate instanceof ShippingRate) {
            $this->ensureShippingOptionEligible($shippingRate, $shippingLocation, $orderItemsSummary);
        }

        $totals = $this->calculateSessionTotals(
            $input,
            $cart,
            $hydratedItems,
            $orderItemsSummary,
            $shippingRate,
            $shippingLocation,
            $billingLocation,
        );

        if (BigDecimal::of($totals->total)->isZero()) {
            $paymentGateway = null;
        } elseif (! $paymentGateway instanceof PaymentGateway) {
            throw ValidationException::withMessages([
                'payment_gateway_id' => __('Please select a payment method.'),
            ]);
        } else {
            $this->ensurePaymentOptionEligible($paymentGateway, $shippingLocation ?? $billingLocation, $orderItemsSummary, $input->currencyCode);
        }

        $reservationExpiresAt = now()->addMinutes((int) Setting::getValue('checkout_reservation_minutes', 10));
        $shippingRate?->loadMissing('carrier:id,name', 'region:id,name');

        $sessionData = $this->buildSessionPayload(
            $input,
            $customer,
            $cart->id,
            $shippingAddress,
            $billingAddress,
            $shippingRate,
            $paymentGateway,
            $totals,
        );

        $session = $existingSession instanceof CheckoutSession
            ? $existingSession->updateReplacingTranslations($sessionData)
            : CheckoutSession::query()->create($sessionData);

        /** @var list<array{product_id: int, product_variant_id?: string|null, quantity: int}> $reservationItems */
        $reservationItems = $cart->items->map(fn (CartItem $item): array => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
        ])->values()->all();

        $this->reserveStockAction->handle($session, $reservationItems, $reservationExpiresAt);

        $this->saveShippingAddressIfRequested($input, $customer);

        return [$session, $cart];
    }

    private function cleanupPendingSessions(string $cartId): ?CheckoutSession
    {
        $pendingSessions = CheckoutSession::query()
            ->where('cart_id', $cartId)
            ->where('status', CheckoutSessionStatus::Pending)
            ->latest()
            ->get();

        $existingSession = $pendingSessions->first();

        foreach ($pendingSessions->skip(1) as $staleSession) {
            $this->cancelCheckoutSessionAction->handle($staleSession);
        }

        if ($existingSession?->step === CheckoutStep::PaymentInitiated) {
            $this->releaseStockReservationsAction->handle($existingSession);
        }

        return $existingSession;
    }

    /**
     * @param  list<HydratedOrderItem>  $hydratedItems
     */
    private function calculateSessionTotals(
        StoreCheckoutInput $input,
        Cart $cart,
        array $hydratedItems,
        OrderItemsSummary $orderItemsSummary,
        ?ShippingRate $shippingRate,
        ?AddressLocation $shippingLocation,
        ?AddressLocation $billingLocation,
    ): CheckoutSessionTotals {
        $subtotal = $orderItemsSummary->subtotal;
        $couponValidationResult = $this->validateCoupon($cart->coupon_code, $subtotal, $input->customerEmail);
        $shippingTotal = $shippingRate->rate ?? '0.0000';
        $discountTotal = $couponValidationResult->discount ?? '0.0000';

        $taxInput = TaxCalculationInput::fromHydratedItems(
            $hydratedItems,
            $subtotal,
            $shippingTotal,
            $discountTotal,
            $shippingLocation,
            $billingLocation,
        );
        $taxTotal = $this->orderTaxCalculator->calculate($taxInput)->taxTotal;

        $baseCurrencyCode = (string) Setting::getValue('base_currency');
        $currencyCode = $input->currencyCode ?? $baseCurrencyCode;
        $exchangeRate = Currency::getExchangeRate($currencyCode);
        $needsConversion = $currencyCode !== $baseCurrencyCode && ! BigDecimal::of($exchangeRate)->isEqualTo(BigDecimal::one());
        $decimalPlaces = Currency::getDecimalPlaces($currencyCode);

        $scaleFn = $needsConversion
            ? fn (string $amount): string => $this->currencyConverter->convert($amount, $exchangeRate, $decimalPlaces)
            : fn (string $amount): string => $this->currencyConverter->round($amount, $decimalPlaces);

        $subtotal = $scaleFn($subtotal);
        $shippingTotal = $scaleFn($shippingTotal);
        $discountTotal = $scaleFn($discountTotal);
        $taxTotal = $scaleFn($taxTotal);

        return new CheckoutSessionTotals(
            subtotal: $subtotal,
            shippingTotal: $shippingTotal,
            discountTotal: $discountTotal,
            taxTotal: $taxTotal,
            total: $this->orderUtility->calculateOrderTotal($subtotal, $taxTotal, $shippingTotal, $discountTotal),
            currencyCode: $currencyCode,
            exchangeRate: $exchangeRate,
            hydratedItems: $this->transformItemPrices($hydratedItems, $scaleFn),
            couponValidationResult: $couponValidationResult,
        );
    }

    /**
     * @param  array<string, mixed>|null  $shippingAddress
     * @param  array<string, mixed>  $billingAddress
     * @return array<string, mixed>
     */
    private function buildSessionPayload(
        StoreCheckoutInput $input,
        ?User $customer,
        string $cartId,
        ?array $shippingAddress,
        array $billingAddress,
        ?ShippingRate $shippingRate,
        ?PaymentGateway $paymentGateway,
        CheckoutSessionTotals $totals,
    ): array {

        return [
            'cart_id' => $cartId,
            'customer_id' => $customer?->id,
            'customer_email' => $input->customerEmail,
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'different_billing_address' => $input->differentBillingAddress,
            'shipping_rate_id' => $shippingRate?->id,
            'shipping_carrier_id' => $shippingRate?->shipping_carrier_id,
            'shipping_carrier_name' => $shippingRate?->carrier?->getTranslations('name'),
            'shipping_rate_name' => $shippingRate?->getTranslations('name'),
            'region_id' => $shippingRate?->region_id,
            'region_name' => $shippingRate?->region?->getTranslations('name') ?: null,
            'payment_gateway_id' => $paymentGateway?->id,
            'payment_gateway_name' => $paymentGateway?->getTranslations('name'),
            'coupon_id' => $totals->couponValidationResult?->coupon->id,
            'coupon_code' => $totals->couponValidationResult?->coupon->code,
            'notes' => $input->notes,
            'items' => $this->serializeItems($totals->hydratedItems),
            'total_quantity' => array_sum(array_column($totals->hydratedItems, 'quantity')),
            'currency_code' => $totals->currencyCode,
            'exchange_rate' => $totals->exchangeRate,
            'prices_include_tax' => (bool) Setting::getValue('prices_include_tax'),
            'shipping_is_taxable' => (bool) Setting::getValue('shipping_is_taxable'),
            'tax_based_on' => Setting::getValue('tax_based_on') ?? TaxBasedOn::Shipping->value,
            'default_tax_rate' => Setting::getValue('default_tax_rate'),
            'tax_store_country_code' => Setting::getValue('store_country_code'),
            'tax_store_state' => Setting::getValue('store_state'),
            'tax_store_postal_code' => Setting::getValue('store_postal_code'),
            'subtotal' => $totals->subtotal,
            'tax_total' => $totals->taxTotal,
            'shipping_total' => $totals->shippingTotal,
            'discount_total' => $totals->discountTotal,
            'total' => $totals->total,
            'status' => CheckoutSessionStatus::Pending,
            'step' => CheckoutStep::PaymentInitiated,
            'expires_at' => now()->addMinutes((int) Setting::getValue('abandoned_checkout_delay_minutes', 60)),
        ];
    }

    private function saveShippingAddressIfRequested(StoreCheckoutInput $input, ?User $customer): void
    {
        if (! $customer || ! $input->saveShippingAddress || ! $input->shippingAddress instanceof Address) {
            return;
        }

        $this->storeCustomerAddressAction->handle($customer, new StoreCustomerAddressInput(
            address: $input->shippingAddress,
            isDefault: false,
        ));
    }

    private function validateCoupon(?string $couponCode, string $subtotal, string $customerEmail): ?CouponValidationResult
    {
        if ($couponCode === null || $couponCode === '') {
            return null;
        }

        return $this->couponValidator->validate(
            $couponCode,
            $subtotal,
            $customerEmail,
        );
    }

    /**
     * @param  list<HydratedOrderItem>  $hydratedItems
     * @return list<array<string, mixed>>
     */
    private function serializeItems(array $hydratedItems): array
    {
        return array_map(fn (HydratedOrderItem $item): array => [
            'product_id' => $item->product->id,
            'product_variant_id' => $item->variant?->id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unitPrice,
            'total_price' => $item->totalPrice,
            'product_title' => $item->productTitle,
            'product_sku' => $item->productSku,
            'variant_title' => $item->variantTitle,
            'variant_options' => $item->variantOptions,
            'requires_shipping' => $item->requiresShipping,
            'weight' => $item->weight,
            'weight_unit' => $item->weightUnit,
            'thumbnail_url' => $item->variant->media->small_thumbnail_url
                ?? $item->product->featured_media->small_thumbnail_url
                ?? null,
        ], $hydratedItems);
    }

    /**
     * @throws ValidationException
     */
    private function resolvePaymentGateway(int $paymentGatewayId): PaymentGateway
    {
        $paymentGateway = PaymentGateway::query()->whereKey($paymentGatewayId)->first();

        if (! $paymentGateway) {
            throw ValidationException::withMessages([
                'payment_gateway_id' => __('Selected payment method does not exist.'),
            ]);
        }

        if (! $paymentGateway->is_active) {
            throw ValidationException::withMessages([
                'payment_gateway_id' => __('Selected payment method is no longer available.'),
            ]);
        }

        return $paymentGateway;
    }

    /**
     * @throws ValidationException
     */
    private function resolveShippingRate(int $shippingRateId): ShippingRate
    {
        $shippingRate = ShippingRate::query()->whereKey($shippingRateId)->first();

        if (! $shippingRate) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => __('Selected shipping method does not exist.'),
            ]);
        }

        if (! $shippingRate->is_active) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => __('Selected shipping method is no longer available.'),
            ]);
        }

        return $shippingRate;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveBillingAddress(StoreCheckoutInput $input): array
    {
        if ($input->shippingAddress instanceof Address) {
            return $input->differentBillingAddress && $input->billingAddress instanceof Address
                ? $input->billingAddress->toArray()
                : $input->shippingAddress->toArray();
        }

        return $input->billingAddress instanceof Address ? $input->billingAddress->toArray() : [];
    }

    /**
     * @throws ValidationException
     */
    private function ensureShippingOptionEligible(ShippingRate $shippingRate, ?AddressLocation $shippingAddress, OrderItemsSummary $orderItemsSummary): void
    {
        $eligibleOptions = $this->eligibleShippingOptionsQuery->execute($orderItemsSummary, $shippingAddress);

        if ($eligibleOptions->doesntContain(fn (array $rate): bool => $rate['id'] === $shippingRate->id)) {
            throw ValidationException::withMessages([
                'shipping_rate_id' => __('Selected shipping method is not available for your order.'),
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function ensurePaymentOptionEligible(PaymentGateway $paymentGateway, ?AddressLocation $shippingAddress, OrderItemsSummary $orderItemsSummary, ?string $currencyCode = null): void
    {
        $eligibleOptions = $this->eligiblePaymentOptionsQuery->execute($orderItemsSummary, $shippingAddress, $currencyCode);

        if ($eligibleOptions->doesntContain(fn (array $option): bool => $option['id'] === $paymentGateway->id)) {
            throw ValidationException::withMessages([
                'payment_gateway_id' => __('Selected payment method is not available for your order.'),
            ]);
        }
    }

    /**
     * @param  list<HydratedOrderItem>  $items
     * @param  Closure(string): string  $transform
     * @return list<HydratedOrderItem>
     */
    private function transformItemPrices(array $items, Closure $transform): array
    {
        return array_map(
            fn (HydratedOrderItem $item): HydratedOrderItem => $item->withPrices(
                $transform($item->unitPrice),
                $transform($item->totalPrice),
            ),
            $items,
        );
    }
}
