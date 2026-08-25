<?php

declare(strict_types=1);

namespace App\Utilities;

use App\DTOs\CouponValidationResult;
use App\Models\Coupon;
use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

final readonly class CouponValidator
{
    public function __construct(
        private CouponDiscountCalculator $couponDiscountCalculator
    ) {
    }

    public function validate(string $couponCode, string $subtotal, ?string $customerEmail = null): CouponValidationResult
    {
        $coupon = Coupon::query()->where('code', Coupon::normalizeCode($couponCode))->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('Invalid coupon code.')],
            ]);
        }

        if (! $this->isValid($coupon)) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('This coupon is no longer valid.')],
            ]);
        }

        if ($customerEmail && ! $this->canBeUsedByCustomer($coupon, $customerEmail)) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('You have reached the usage limit for this coupon.')],
            ]);
        }

        $discount = $this->couponDiscountCalculator->calculate($coupon, $subtotal);

        if (BigDecimal::of($discount)->isNegativeOrZero()) {
            throw ValidationException::withMessages([
                'coupon_code' => [__('This coupon cannot be applied to your order.')],
            ]);
        }

        return new CouponValidationResult($coupon, $discount);
    }

    private function isValid(Coupon $coupon): bool
    {
        if (! $coupon->is_active) {
            return false;
        }

        $now = now();

        if ($coupon->starts_at && $now->isBefore($coupon->starts_at)) {
            return false;
        }

        if ($coupon->expires_at && $now->isAfter($coupon->expires_at)) {
            return false;
        }

        return ! $coupon->usage_limit || $coupon->used_count < $coupon->usage_limit;
    }

    private function canBeUsedByCustomer(Coupon $coupon, string $customerEmail): bool
    {
        if (! $coupon->usage_limit_per_customer) {
            return true;
        }

        $customerUsageCount = $coupon->orders()->where('customer_email', $customerEmail)->count();

        return $customerUsageCount < $coupon->usage_limit_per_customer;
    }
}
