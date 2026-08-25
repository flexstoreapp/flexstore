<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Payment\DTOs\SessionResult;

final readonly class CheckoutInitiationResult
{
    public function __construct(
        public CheckoutSession $checkoutSession,
        public SessionResult $paymentSession,
        public Cart $cart,
    ) {
    }
}
