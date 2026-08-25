<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CheckoutOptionsInput;
use App\DTOs\UpdateCartInput;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ApplyCheckoutOptionsAction
{
    public function __construct(
        private UpdateCartAction $updateCartAction,
        private UpdatePendingCheckoutSessionAction $updateSessionAction,
    ) {
    }

    public function handle(Cart $cart, CheckoutOptionsInput $input, ?User $user, ?string $currencyCode): void
    {
        DB::transaction(function () use ($cart, $input, $user, $currencyCode): void {
            $cartInput = UpdateCartInput::fromArray([
                ...($input->has('shipping_rate_id') ? ['shipping_rate_id' => $input->shippingRateId] : []),
                ...($input->has('shipping_quote_reference') ? ['shipping_quote_reference' => $input->shippingQuoteReference] : []),
                ...($input->has('payment_gateway_id') ? ['payment_gateway_id' => $input->paymentGatewayId] : []),
            ]);

            $this->updateCartAction->handle($cart, $cartInput);
            $this->updateSessionAction->handle(
                cartId: $cart->id,
                input: $input,
                customerEmail: $user?->email,
                customerId: $user?->id,
                currencyCode: $currencyCode,
            );
        });
    }
}
