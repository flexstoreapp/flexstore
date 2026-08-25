<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\TaxCalculationInput;
use App\Models\Currency;
use App\Models\Order;
use App\Utilities\OrderTaxCalculator;

final readonly class StoreOrderTaxAction
{
    public function __construct(
        private OrderTaxCalculator $orderTaxCalculator,
        private StoreTaxDetailsAction $storeTaxDetailsAction,
        private UpdateOrderItemTaxAmountsAction $updateOrderItemTaxAmountsAction,
    ) {
    }

    public function handle(Order $order): void
    {
        $decimalPlaces = Currency::getDecimalPlaces($order->currency_code);

        $result = $this->orderTaxCalculator
            ->calculate(TaxCalculationInput::fromOrder($order))
            ->scaledTo($decimalPlaces);

        $order->taxDetails()->delete();

        $this->storeTaxDetailsAction->handle($order->id, $result->taxDetails);

        $this->updateOrderItemTaxAmountsAction->handle($order, $result->taxDetails);

        $order->update(['tax_total' => $result->taxTotal]);
    }
}
