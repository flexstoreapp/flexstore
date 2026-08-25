import { useMemo } from 'react';

import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { AddressSummaryCard } from '@/components/admin/address-summary-card';
import { CustomerSummaryCard } from '@/components/admin/customer-summary-card';
import { DigitalItemsCard } from '@/components/admin/order/digital-items-card';
import { FulfilledCard } from '@/components/admin/order/fulfilled-card';
import { OrderActivities } from '@/components/admin/order/order-activities';
import { OrderHeader } from '@/components/admin/order/order-header';
import { OrderNotes } from '@/components/admin/order/order-notes';
import { OrderPaymentCard } from '@/components/admin/order/order-payment-card';
import { OrderRefundedCard } from '@/components/admin/order/order-refunded-card';
import { UnfulfilledCard } from '@/components/admin/order/unfulfilled-card';
import { __ } from '@/lib/i18n';
import { type ItemBreakdown, getFulfillmentGroups } from '@/lib/order-item-groups';
import type { Order } from '@/types';
import type { DisplayTaxTotals } from '@/types/setting';

interface CustomerStats {
    name: string | null;
    orders_count: number;
    lifetime_value: string;
    created_at: string;
}

interface OrderShowProps {
    order: Order;
    itemBreakdown: Record<number, ItemBreakdown>;
    displayTaxTotals: DisplayTaxTotals;
    canRecordPayment?: boolean;
    canRefundCredit?: boolean;
    customerStats: CustomerStats | null;
}

export default function OrderShow({
    order,
    itemBreakdown,
    displayTaxTotals,
    canRecordPayment,
    canRefundCredit,
    customerStats,
}: OrderShowProps) {
    const fulfillmentGroups = useMemo(() => getFulfillmentGroups(order, itemBreakdown), [order, itemBreakdown]);
    const requiresShipping = (order.items ?? []).some((item) => item.requires_shipping);

    return (
        <>
            <OrderHeader order={order} />

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    {fulfillmentGroups.map((group) =>
                        group.type === 'unfulfilled' ? (
                            <UnfulfilledCard key="unfulfilled" order={order} items={group.items} />
                        ) : group.type === 'fulfilled' ? (
                            <FulfilledCard
                                key={group.shipment.id}
                                order={order}
                                shipment={group.shipment}
                                items={group.items}
                            />
                        ) : group.type === 'digital' ? (
                            <DigitalItemsCard key="digital" order={order} items={group.items} />
                        ) : (
                            <OrderRefundedCard key="refunded" order={order} items={group.items} />
                        ),
                    )}

                    <OrderPaymentCard
                        order={order}
                        displayTaxTotals={displayTaxTotals}
                        canRecordPayment={canRecordPayment}
                        canRefundCredit={canRefundCredit}
                    />

                    <div className="hidden lg:contents">
                        <OrderActivities order={order} />
                    </div>
                </div>

                <div className="space-y-6">
                    {order.notes && <OrderNotes notes={order.notes} />}
                    <CustomerSummaryCard
                        customerId={order.customer_id}
                        email={order.customer_email}
                        billingAddress={order.billing_address ?? null}
                        shippingAddress={order.shipping_address ?? null}
                        description={__('Customer details for this order')}
                        stats={customerStats}
                    />
                    {requiresShipping && (
                        <AddressSummaryCard
                            title={__('Shipping address')}
                            description={__('Where the order will be delivered')}
                            address={order.shipping_address ?? null}
                        />
                    )}
                    <AddressSummaryCard
                        title={__('Billing address')}
                        description={__('Address associated with the payment')}
                        address={order.billing_address ?? null}
                    />
                    <div className="lg:hidden">
                        <OrderActivities order={order} />
                    </div>
                </div>
            </div>
        </>
    );
}

OrderShow.layout = ({ order }: OrderShowProps) => ({
    breadcrumbs: [
        { title: __('Orders'), href: OrderController.index() },
        { title: `#${order.id}`, href: OrderController.show(order) },
    ],
});
