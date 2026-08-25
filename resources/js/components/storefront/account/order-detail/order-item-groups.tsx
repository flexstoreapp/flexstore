import { GroupCard } from '@/components/storefront/order/group-card';
import { multiply } from '@/lib/decimal';
import { getFulfillmentGroups, type FulfillmentGroupItem, type ItemBreakdown } from '@/lib/order-item-groups';
import { getTranslation } from '@/lib/utils';
import type { Order, OrderLine, OrderShipmentGroup } from '@/types';

function toLine({ orderItem, quantity }: FulfillmentGroupItem): OrderLine {
    return {
        product_title: orderItem.product_title,
        variant_title: orderItem.variant_title,
        url_handle: orderItem.product?.is_active ? orderItem.product.url_handle : null,
        featured_media: orderItem.media ?? null,
        quantity,
        total_price: multiply(orderItem.unit_price, quantity),
    };
}

interface OrderItemGroupsProps {
    order: Order;
    itemBreakdown: Record<number, ItemBreakdown>;
}

export function OrderItemGroups({ order, itemBreakdown }: OrderItemGroupsProps) {
    const carrierName = order.shipping_carrier_name ? getTranslation(order.shipping_carrier_name) : '';

    const groups: OrderShipmentGroup[] = getFulfillmentGroups(order, itemBreakdown).map((group) => {
        const base = {
            carrier_name: '',
            tracking_number: null,
            tracking_url: null,
            shipped: false,
            items: group.items.map(toLine),
        } satisfies Omit<OrderShipmentGroup, 'key'>;

        switch (group.type) {
            case 'fulfilled':
                return {
                    ...base,
                    key: `shipment-${group.shipment.id}`,
                    carrier_name: carrierName,
                    tracking_number: group.shipment.tracking_number,
                    tracking_url: group.shipment.tracking_url,
                    shipped: group.shipment.shipped_at !== null,
                };
            case 'digital':
                return { ...base, key: 'digital', digital: true };
            case 'refunded':
                return { ...base, key: 'refunded', refunded: true };
            default:
                return { ...base, key: 'unshipped' };
        }
    });

    return (
        <>
            {groups.map((group, index) => (
                <GroupCard
                    key={group.key}
                    group={group}
                    index={index + 1}
                    total={groups.length}
                    currencyCode={order.currency_code}
                />
            ))}
        </>
    );
}
