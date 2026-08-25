<?php

declare(strict_types=1);

namespace App\Utilities;

use App\DTOs\RefundCalculationResult;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class RefundCalculator
{
    /**
     * @param  array<int, int>  $itemQuantities  [order_item_id => refund_quantity]
     */
    public function calculate(Order $order, array $itemQuantities): RefundCalculationResult
    {
        $order->loadMissing('items');

        $scale = Currency::getDecimalPlaces($order->currency_code);
        $orderItems = $order->items->keyBy('id');

        $productTotal = BigDecimal::zero();
        $taxTotal = BigDecimal::zero();
        $discountTotal = BigDecimal::zero();

        foreach ($itemQuantities as $orderItemId => $quantity) {
            $orderItem = $orderItems[$orderItemId] ?? null;

            if (! $orderItem) {
                continue;
            }

            $productTotal = $productTotal->plus(
                BigDecimal::of($orderItem->unit_price)->multipliedBy($quantity)
            );

            $taxTotal = $taxTotal->plus($this->calculateTaxForItem($orderItem, $quantity));
            $discountTotal = $discountTotal->plus($this->calculateDiscountForItem($orderItem, $quantity, $order));
        }

        return new RefundCalculationResult(
            productTotal: $productTotal->toScale($scale, RoundingMode::HalfUp)->toString(),
            taxTotal: $taxTotal->toScale($scale, RoundingMode::HalfUp)->toString(),
            discountTotal: $discountTotal->toScale($scale, RoundingMode::HalfUp)->toString(),
        );
    }

    private function calculateTaxForItem(OrderItem $item, int $quantity): BigDecimal
    {
        if (! $item->tax_amount || $item->quantity <= 0) {
            return BigDecimal::zero();
        }

        $taxPerUnit = BigDecimal::of($item->tax_amount)
            ->dividedBy($item->quantity, 4, RoundingMode::HalfUp);

        return $taxPerUnit->multipliedBy($quantity);
    }

    private function calculateDiscountForItem(OrderItem $item, int $quantity, Order $order): BigDecimal
    {
        if (BigDecimal::of($order->discount_total)->isZero() || BigDecimal::of($order->subtotal)->isZero()) {
            return BigDecimal::zero();
        }

        $itemProportion = BigDecimal::of($item->total_price)
            ->dividedBy($order->subtotal, 4, RoundingMode::HalfUp);

        $itemDiscountTotal = BigDecimal::of($order->discount_total)->multipliedBy($itemProportion);

        $discountPerUnit = $itemDiscountTotal->dividedBy($item->quantity, 4, RoundingMode::HalfUp);

        return $discountPerUnit->multipliedBy($quantity);
    }
}
