<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Order;
use App\Queries\RefundableOrderDataQuery;
use Brick\Math\BigDecimal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class ValidRefundShippingAmount implements ValidationRule
{
    public function __construct(
        private Order $order
    ) {
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail(__('The shipping refund amount must be a valid number.'));

            return;
        }

        if (BigDecimal::of((string) $value)->isNegative()) {
            $fail(__('The shipping refund amount cannot be negative.'));

            return;
        }

        $maxShipping = resolve(RefundableOrderDataQuery::class)->execute($this->order)['refundable_shipping_amount'];

        if (BigDecimal::of((string) $value)->isGreaterThan(BigDecimal::of($maxShipping))) {
            $fail(__('The shipping refund amount cannot exceed :amount.', ['amount' => $maxShipping]));
        }
    }
}
