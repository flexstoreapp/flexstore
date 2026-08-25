<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Currency;
use App\Models\Order;
use App\Utilities\RefundCalculator;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidRefundTotal implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    public function __construct(
        private readonly Order $order
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $scale = Currency::getDecimalPlaces($this->order->currency_code);

        $isOverridden = (bool) ($this->data['is_manual_total'] ?? false);

        $total = $isOverridden && is_numeric($value)
            ? BigDecimal::of((string) $value)->toScale($scale, RoundingMode::HalfUp)
            : $this->calculateTotal($scale);

        $maxRefundable = BigDecimal::of($this->order->net_paid_total)
            ->toScale($scale, RoundingMode::HalfUp);

        if ($total->isNegativeOrZero()) {
            $fail(__('The total refund amount must be greater than zero.'));

            return;
        }

        if ($total->isGreaterThan($maxRefundable)) {
            $fail(__('The total refund amount cannot exceed :amount.', ['amount' => $maxRefundable->toString()]));
        }
    }

    /**
     * @param  int<0, max>  $scale
     */
    private function calculateTotal(int $scale): BigDecimal
    {
        $items = $this->data['items'] ?? [];

        if (! is_array($items)) {
            return BigDecimal::zero();
        }

        $itemQuantities = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (! isset($item['order_item_id'], $item['quantity'])) {
                continue;
            }
            $itemQuantities[$item['order_item_id']] = (int) $item['quantity'];
        }

        $totals = resolve(RefundCalculator::class)->calculate($this->order, $itemQuantities);

        $shippingAmount = isset($this->data['shipping_amount']) && is_numeric($this->data['shipping_amount'])
            ? BigDecimal::of((string) $this->data['shipping_amount'])
            : BigDecimal::zero();

        return BigDecimal::of($totals->productTotal)
            ->minus($totals->discountTotal)
            ->plus($totals->taxTotal)
            ->plus($shippingAmount)
            ->toScale($scale, RoundingMode::HalfUp);
    }
}
