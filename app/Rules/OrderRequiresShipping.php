<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class OrderRequiresShipping implements ValidationRule
{
    public function __construct(private Order $order)
    {
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->order->requiresShipping()) {
            $fail(__('This order has no items that require shipping.'));
        }
    }
}
