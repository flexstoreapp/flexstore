<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\TaxDetailInput;
use App\Models\Order;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class UpdateOrderItemTaxAmountsAction
{
    /**
     * @param  list<TaxDetailInput>  $taxDetails
     */
    public function handle(Order $order, array $taxDetails): void
    {
        $itemTaxTotals = $this->calculateItemTaxTotals($taxDetails);

        foreach ($itemTaxTotals as $itemId => $totalTax) {
            $order->items()->whereKey($itemId)->update(['tax_amount' => $totalTax]);
        }
    }

    /**
     * @param  list<TaxDetailInput>  $taxDetails
     * @return array<int, string>
     */
    private function calculateItemTaxTotals(array $taxDetails): array
    {
        $itemTaxTotals = [];

        foreach ($taxDetails as $taxDetail) {
            if ($taxDetail->orderItemId !== null) {
                $currentTotal = $itemTaxTotals[$taxDetail->orderItemId] ?? BigDecimal::zero();
                $itemTaxTotals[$taxDetail->orderItemId] = $currentTotal->plus($taxDetail->taxAmount);
            }
        }

        return array_map(
            fn (BigDecimal $amount): string => $amount->toScale(4, RoundingMode::HalfUp)->toString(),
            $itemTaxTotals
        );
    }
}
