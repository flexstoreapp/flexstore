<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\CouponType;
use App\Models\Coupon;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class CouponDiscountCalculator
{
    public function calculate(Coupon $coupon, string $subtotal): string
    {
        $subtotalAmount = BigDecimal::of($subtotal);

        if ($this->isSubtotalBelowMinimum($coupon, $subtotalAmount)) {
            return '0.0000';
        }

        $discount = $this->calculateRawDiscount($coupon, $subtotalAmount);
        $discount = $this->applyMaximumDiscountLimit($coupon, $discount);

        return $this->formatFinalDiscount($discount, $subtotalAmount);
    }

    private function isSubtotalBelowMinimum(Coupon $coupon, BigDecimal $subtotalAmount): bool
    {
        if (! $coupon->min_order_value) {
            return false;
        }

        return $subtotalAmount->isLessThan($coupon->min_order_value);
    }

    private function calculateRawDiscount(Coupon $coupon, BigDecimal $subtotalAmount): BigDecimal
    {
        return match ($coupon->type) {
            CouponType::Flat => BigDecimal::of($coupon->value),
            CouponType::Percentage => $this->calculatePercentageDiscount($coupon, $subtotalAmount),
        };
    }

    private function calculatePercentageDiscount(Coupon $coupon, BigDecimal $subtotalAmount): BigDecimal
    {
        return $subtotalAmount
            ->multipliedBy($coupon->value)
            ->dividedBy(100, 4, RoundingMode::HalfUp);
    }

    private function applyMaximumDiscountLimit(Coupon $coupon, BigDecimal $discount): BigDecimal
    {
        if (! $coupon->maximum_discount) {
            return $discount;
        }

        $maximumDiscount = BigDecimal::of($coupon->maximum_discount);

        return $discount->isGreaterThan($maximumDiscount)
            ? $maximumDiscount
            : $discount;
    }

    private function formatFinalDiscount(BigDecimal $discount, BigDecimal $subtotalAmount): string
    {
        $finalDiscount = $discount->isLessThan($subtotalAmount)
            ? $discount
            : $subtotalAmount;

        return $finalDiscount->toScale(4, RoundingMode::HalfUp)->toString();
    }
}
