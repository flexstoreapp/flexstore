import { OrderDetailHeader } from '@/components/storefront/account/order-detail/order-detail-header';
import { OrderDownloadsCard } from '@/components/storefront/account/order-detail/order-downloads-card';
import { OrderItemGroups } from '@/components/storefront/account/order-detail/order-item-groups';
import { OrderSummaryPanel } from '@/components/storefront/account/order-detail/order-summary-panel';
import { AddressCard } from '@/components/storefront/order/address-card';
import { Section } from '@/components/storefront/section';
import { __ } from '@/lib/i18n';
import { type ItemBreakdown } from '@/lib/order-item-groups';
import type { DisplayTaxTotals, Order } from '@/types';

interface OrderShowProps {
    order: Order;
    displayTaxTotals: DisplayTaxTotals;
    itemBreakdown: Record<number, ItemBreakdown>;
}

export default function OrderShow({ order, displayTaxTotals, itemBreakdown }: OrderShowProps) {
    return (
        <>
            <OrderDetailHeader order={order} />

            <Section className="mt-6 grid grid-cols-1 items-start gap-6 pb-12 lg:grid-cols-[1fr_380px] lg:gap-8 xl:grid-cols-[1fr_420px]">
                <div className="flex flex-col gap-6">
                    <OrderItemGroups order={order} itemBreakdown={itemBreakdown} />
                    <OrderDownloadsCard downloads={order.item_downloads ?? []} />
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

                <OrderSummaryPanel order={order} displayTaxTotals={displayTaxTotals} />
            </Section>
        </>
    );
}
