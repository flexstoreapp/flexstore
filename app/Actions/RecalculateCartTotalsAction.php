<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Media;
use App\Utilities\CouponDiscountCalculator;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class RecalculateCartTotalsAction
{
    public function __construct(
        private CouponDiscountCalculator $couponDiscountCalculator,
    ) {
    }

    public function handle(Cart $cart): Cart
    {
        $cart->load('items.product:id,title,url_handle,price,type');

        $subtotal = $cart->items->reduce(
            fn (BigDecimal $carry, CartItem $cartItem): BigDecimal => $carry->plus($cartItem->total_price),
            BigDecimal::zero()
        );

        $subtotalString = $subtotal->toScale(4, RoundingMode::HalfUp)->toString();
        $discountTotal = $this->recalculateCouponDiscount($cart, $subtotalString);
        $dropShipping = $cart->items->isNotEmpty() && ! $cart->requiresShipping();
        $shippingTotal = $dropShipping ? BigDecimal::zero() : BigDecimal::of($cart->shipping_total ?? '0');
        $taxTotal = BigDecimal::of($cart->tax_total ?? '0');

        $total = $subtotal
            ->minus($discountTotal)
            ->plus($shippingTotal)
            ->plus($taxTotal);

        if ($total->isNegative()) {
            $total = BigDecimal::zero();
        }

        $cart->update([
            'subtotal' => $subtotalString,
            'discount_total' => $discountTotal->toScale(4, RoundingMode::HalfUp)->toString(),
            'shipping_total' => $shippingTotal->toScale(4, RoundingMode::HalfUp)->toString(),
            'tax_total' => $taxTotal->toScale(4, RoundingMode::HalfUp)->toString(),
            'total' => $total->toScale(4, RoundingMode::HalfUp)->toString(),
            ...($dropShipping ? ['shipping_rate_id' => null, 'shipping_quote_reference' => null] : []),
        ]);

        return $cart->loadMissing([
            'items.product:id,url_handle,title,type,weight,weight_unit',
            'items.product.mediaGallery' => fn (Relation $q): Relation => $q->select(Media::displayColumns())->limit(1),
            'items.productVariant:id,product_id,media_id,weight,weight_unit',
            'items.productVariant.media:' . Media::displaySelect(),
        ]);
    }

    private function recalculateCouponDiscount(Cart $cart, string $subtotal): BigDecimal
    {
        if (empty($cart->coupon_code)) {
            return BigDecimal::zero();
        }

        $coupon = Coupon::query()
            ->where('code', $cart->coupon_code)
            ->where('is_active', true)
            ->first(['id', 'code', 'type', 'value', 'is_active', 'min_order_value', 'maximum_discount']);

        if (! $coupon) {
            $cart->update(['coupon_code' => null]);

            return BigDecimal::zero();
        }

        $discount = $this->couponDiscountCalculator->calculate($coupon, $subtotal);

        return BigDecimal::of($discount);
    }
}
