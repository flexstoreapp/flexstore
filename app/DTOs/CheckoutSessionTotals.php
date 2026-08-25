<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CheckoutSessionTotals
{
    /**
     * @param  list<HydratedOrderItem>  $hydratedItems
     */
    public function __construct(
        public string $subtotal,
        public string $shippingTotal,
        public string $discountTotal,
        public string $taxTotal,
        public string $total,
        public string $currencyCode,
        public string $exchangeRate,
        public array $hydratedItems,
        public ?CouponValidationResult $couponValidationResult,
    ) {
    }
}
