import { Link, usePage } from '@inertiajs/react';

import * as CheckoutController from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import { buttonVariants } from '@/components/storefront/button';
import { CouponForm } from '@/components/storefront/coupon-form';
import { OrderSummaryBreakdown } from '@/components/storefront/order-summary-breakdown';
import { OrderSummaryCard } from '@/components/storefront/order-summary-card';
import { OrderSummaryTotal } from '@/components/storefront/order-summary-total';
import { isPositive } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { DisplayTaxTotals, StorefrontSharedData } from '@/types';

interface CartSummaryProps {
    itemCount: number;
    pricesIncludeTax: boolean;
    displayTaxTotals: DisplayTaxTotals;
}

export function CartSummary({ itemCount, pricesIncludeTax, displayTaxTotals }: CartSummaryProps) {
    const { cart, auth } = usePage<StorefrontSharedData>().props;
    const showCoupon = Boolean(auth.user) || Boolean(cart.coupon_code);

    return (
        <OrderSummaryCard>
            {showCoupon && (
                <div className="mt-5">
                    <CouponForm inputId="cart-coupon" />
                </div>
            )}

            <OrderSummaryBreakdown
                subtotal={cart.subtotal}
                discountTotal={cart.discount_total}
                shippingTotal={cart.shipping_total}
                taxTotal={cart.tax_total}
                itemCount={itemCount}
                requiresShipping={cart.requires_shipping}
                shippingResolved={isPositive(cart.shipping_total)}
                pendingShippingLabel={__('Calculated at checkout')}
                pricesIncludeTax={pricesIncludeTax}
                displayTaxTotals={displayTaxTotals}
            />

            <OrderSummaryTotal total={cart.total} />

            <Link
                href={CheckoutController.create()}
                className={cn(buttonVariants({ size: 'lg', block: true }), 'mt-5')}
            >
                {__('Proceed to checkout')}
            </Link>
        </OrderSummaryCard>
    );
}
