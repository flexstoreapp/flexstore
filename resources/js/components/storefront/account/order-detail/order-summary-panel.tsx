import { OrderSummaryAside } from '@/components/storefront/order/summary-aside';
import { OrderSummaryBreakdown } from '@/components/storefront/order-summary-breakdown';
import { OrderSummaryTotal } from '@/components/storefront/order-summary-total';
import { useFormatId } from '@/hooks/use-format-id';
import { useFormatMoney } from '@/hooks/use-format-money';
import { isPositive } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import type { DisplayTaxTotals, Order } from '@/types';

export function OrderSummaryPanel({ order, displayTaxTotals }: { order: Order; displayTaxTotals: DisplayTaxTotals }) {
    const formatId = useFormatId();
    const { formatMoney } = useFormatMoney();
    const items = order.items ?? [];
    const itemCount = items.reduce((total, item) => total + item.quantity, 0);

    return (
        <OrderSummaryAside orderNumber={formatId(order.id)}>
            <div className="rounded-md border border-line bg-surface p-6">
                <h2 className="m-0 text-xl font-semibold text-ink">{__('Order summary')}</h2>
                <OrderSummaryBreakdown
                    subtotal={order.subtotal}
                    discountTotal={order.discount_total}
                    shippingTotal={order.shipping_total}
                    taxTotal={order.tax_total}
                    itemCount={itemCount}
                    requiresShipping={items.some((item) => item.requires_shipping)}
                    shippingResolved
                    pricesIncludeTax={order.prices_include_tax}
                    displayTaxTotals={displayTaxTotals}
                    taxDetails={order.tax_details}
                    currencyCode={order.currency_code}
                    topBorder={false}
                />
                <OrderSummaryTotal total={order.total} currencyCode={order.currency_code} />
                {isPositive(order.refund_total) && (
                    <div className="mt-3 flex items-center justify-between text-sm">
                        <span className="text-ink">{__('Refunded')}</span>
                        <span className="font-semibold text-ink">
                            <span dir="ltr">−{formatMoney(order.refund_total, order.currency_code)}</span>
                        </span>
                    </div>
                )}
            </div>
        </OrderSummaryAside>
    );
}
