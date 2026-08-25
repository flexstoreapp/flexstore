import { AddressCard } from '@/components/storefront/order/address-card';
import { GroupCard } from '@/components/storefront/order/group-card';
import { OrderSummaryAside } from '@/components/storefront/order/summary-aside';
import { OrderSummaryBreakdown } from '@/components/storefront/order-summary-breakdown';
import { OrderSummaryCard } from '@/components/storefront/order-summary-card';
import { OrderSummaryTotal } from '@/components/storefront/order-summary-total';
import { TrackOrderHeader } from '@/components/storefront/track-order/track-order-header';
import { useFormatId } from '@/hooks/use-format-id';
import { __ } from '@/lib/i18n';
import type { TrackedOrderData } from '@/types';

export function TrackOrderResult({ order }: { order: TrackedOrderData }) {
    const formatId = useFormatId();

    const itemCount = order.groups.reduce(
        (total, group) => total + group.items.reduce((sum, item) => sum + item.quantity, 0),
        0,
    );

    return (
        <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_380px] lg:gap-8 xl:grid-cols-[1fr_420px]">
            <div className="flex flex-col gap-6">
                <TrackOrderHeader order={order} />

                {order.groups.map((group, index) => (
                    <GroupCard
                        key={group.key}
                        group={group}
                        index={index + 1}
                        total={order.groups.length}
                        currencyCode={order.currency_code}
                    />
                ))}

                {(order.shipping_address || order.billing_address) && (
                    <div className="grid gap-6 md:grid-cols-2">
                        {order.shipping_address && (
                            <AddressCard title={__('Shipping address')} address={order.shipping_address} />
                        )}
                        {order.billing_address && (
                            <AddressCard title={__('Billing address')} address={order.billing_address} />
                        )}
                    </div>
                )}
            </div>

            <OrderSummaryAside orderNumber={formatId(order.id)}>
                <OrderSummaryCard>
                    <OrderSummaryBreakdown
                        subtotal={order.subtotal}
                        discountTotal={order.discount_total}
                        shippingTotal={order.shipping_total}
                        taxTotal={order.tax_total}
                        itemCount={itemCount}
                        requiresShipping={Boolean(order.shipping_address)}
                        shippingResolved
                        pricesIncludeTax={order.prices_include_tax}
                        displayTaxTotals="single"
                        currencyCode={order.currency_code}
                        topBorder={false}
                    />
                    <OrderSummaryTotal total={order.total} currencyCode={order.currency_code} />
                </OrderSummaryCard>
            </OrderSummaryAside>
        </div>
    );
}
