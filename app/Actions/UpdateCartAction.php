<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateCartInput;
use App\Models\Cart;
use App\Models\ShippingRate;

final readonly class UpdateCartAction
{
    public function __construct(
        private RecalculateCartTotalsAction $recalculateCartTotalsAction,
    ) {
    }

    public function handle(Cart $cart, UpdateCartInput $input): Cart
    {
        $shippingChanged = $input->has('shipping_rate_id');
        $shippingRate = null;

        if ($shippingChanged && $input->shippingRateId !== null) {
            $shippingRate = ShippingRate::query()->findOrFail($input->shippingRateId);
        }

        [$shippingTotal, $quoteReference] = $shippingChanged
            ? $this->resolveShipping($shippingRate)
            : [$cart->shipping_total, $cart->shipping_quote_reference];

        $cart->update([
            'shipping_rate_id' => $shippingChanged ? $input->shippingRateId : $cart->shipping_rate_id,
            'shipping_quote_reference' => $quoteReference,
            'shipping_total' => $shippingTotal,
            'payment_gateway_id' => $input->has('payment_gateway_id') ? $input->paymentGatewayId : $cart->payment_gateway_id,
        ]);

        return $shippingChanged
            ? $this->recalculateCartTotalsAction->handle($cart)
            : $cart;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function resolveShipping(?ShippingRate $rate): array
    {
        if (! $rate instanceof ShippingRate) {
            return ['0.0000', null];
        }

        return [$rate->rate ?? '0.0000', null];
    }
}
