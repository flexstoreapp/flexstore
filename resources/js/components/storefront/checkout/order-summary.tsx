import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { CouponForm } from '@/components/storefront/coupon-form';
import { LineItem } from '@/components/storefront/line-item';
import { OrderSummaryBreakdown } from '@/components/storefront/order-summary-breakdown';
import { OrderSummaryCard } from '@/components/storefront/order-summary-card';
import { OrderSummaryTotal } from '@/components/storefront/order-summary-total';
import { PaymentSummaryActions } from '@/components/storefront/payment-summary-actions';
import { __ } from '@/lib/i18n';
import { lineItemMedia } from '@/lib/media';
import { getTranslation } from '@/lib/utils';

import { useCheckout } from './checkout-context';

export function OrderSummary() {
    const {
        items,
        summary,
        gateway,
        canSubmit,
        generalErrors,
        requiresShipping,
        shippingRateName,
        pricesIncludeTax,
        displayTaxTotals,
        taxDetails,
        coupon,
        email,
    } = useCheckout();

    const itemCount = items.reduce((total, item) => total + item.quantity, 0);

    return (
        <OrderSummaryCard>
            <ul className="m-0 mt-5 flex list-none flex-col gap-4 p-0">
                {items.map((item) => (
                    <LineItem
                        key={item.id}
                        title={getTranslation(item.product?.title)}
                        variantTitle={item.variant_title}
                        url={item.product ? ProductController.show(item.product.url_handle) : null}
                        media={lineItemMedia(item)}
                        quantity={item.quantity}
                        price={item.total_price}
                    />
                ))}
            </ul>

            <div className="mt-5 border-t border-line pt-5">
                <CouponForm
                    inputId="co-coupon"
                    couponCode={coupon.couponCode}
                    onCouponCodeChange={coupon.onCouponCodeChange}
                    email={email}
                />
            </div>

            <OrderSummaryBreakdown
                subtotal={summary.subtotal}
                discountTotal={summary.discount_total}
                shippingTotal={summary.shipping_total}
                taxTotal={summary.tax_total}
                itemCount={itemCount}
                requiresShipping={requiresShipping}
                shippingResolved={Boolean(shippingRateName)}
                pricesIncludeTax={pricesIncludeTax}
                displayTaxTotals={displayTaxTotals}
                taxDetails={taxDetails}
            />

            <OrderSummaryTotal total={summary.total} />

            <PaymentSummaryActions
                gateway={gateway}
                generalErrors={generalErrors}
                submitDisabled={!canSubmit}
                submitLabel={gateway.submitButtonText || __('Place order')}
            ></PaymentSummaryActions>
        </OrderSummaryCard>
    );
}
