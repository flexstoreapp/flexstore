<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ResolveVisitorCartAction;
use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use App\DTOs\TaxCalculationInput;
use App\Enums\DisplayTaxTotals;
use App\Http\Requests\Storefront\CheckoutShippingOptionsRequest;
use App\Models\CartItem;
use App\Models\Currency;
use App\Models\Setting;
use App\Queries\CheckoutShippingOptionsQuery;
use App\Utilities\CartCookie;
use App\Utilities\OrderTaxCalculator;
use App\Utilities\OrderUtility;
use Illuminate\Http\JsonResponse;

final readonly class CheckoutShippingOptionController
{
    public function index(
        CheckoutShippingOptionsRequest $request,
        OrderUtility $orderUtility,
        ResolveVisitorCartAction $resolveVisitorCart,
        CheckoutShippingOptionsQuery $shippingOptionsQuery,
        OrderTaxCalculator $orderTaxCalculator,
    ): JsonResponse {
        $cart = $resolveVisitorCart->handle(CartCookie::from($request), $request->user());

        $hydratedItems = $orderUtility->hydrateItems(
            $cart->items->map(fn (CartItem $item): array => [
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
            ])->all(),
        );

        $orderItemsSummary = OrderItemsSummary::fromHydratedItems($hydratedItems);
        $shippingAddressArr = $request->validated('shipping_address');
        $billingAddressArr = ! empty($request->validated('different_billing_address')) && $request->validated('billing_address')
            ? $request->validated('billing_address')
            : ($shippingAddressArr ?? $request->validated('billing_address'));

        $shippingAddress = is_array($shippingAddressArr) ? AddressLocation::fromArray($shippingAddressArr) : null;
        $billingAddress = is_array($billingAddressArr) ? AddressLocation::fromArray($billingAddressArr) : null;

        $taxInput = TaxCalculationInput::fromHydratedItems(
            $hydratedItems,
            $orderItemsSummary->subtotal,
            $cart->shipping_total ?? '0.0000',
            $cart->discount_total ?? '0.0000',
            $shippingAddress,
            $billingAddress,
        );

        $baseCurrency = (string) Setting::getValue('base_currency');
        $currencyCode = $request->attributes->get('active_currency') ?? $baseCurrency;
        $decimalPlaces = Currency::getDecimalPlaces($currencyCode);

        $taxResult = $orderTaxCalculator->calculate($taxInput)->scaledTo($decimalPlaces);

        $response = [
            'shipping' => $shippingOptionsQuery->execute($orderItemsSummary, $shippingAddress),
            'tax_estimate' => $taxResult->taxTotal,
        ];

        if (DisplayTaxTotals::from(Setting::getValue('display_tax_totals')) === DisplayTaxTotals::Itemized) {
            $response['tax_details'] = $taxResult->aggregatedTaxDetails();
        }

        return response()->json($response);
    }
}
