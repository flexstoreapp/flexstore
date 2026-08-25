import type { Order, OrderItem, OrderShipment } from '@/types';

export interface ItemBreakdown {
    unfulfilled: number;
    refunded: number;
    total_refunded: number;
}

export interface FulfillmentGroupItem {
    orderItem: OrderItem;
    quantity: number;
    shippedRefunded?: number;
}

export type FulfillmentGroup =
    | { type: 'unfulfilled'; items: FulfillmentGroupItem[] }
    | { type: 'fulfilled'; shipment: OrderShipment; items: FulfillmentGroupItem[] }
    | { type: 'digital'; items: FulfillmentGroupItem[] }
    | { type: 'refunded'; items: FulfillmentGroupItem[] };

export function getFulfillmentGroups(order: Order, itemBreakdown: Record<number, ItemBreakdown>): FulfillmentGroup[] {
    const groups: FulfillmentGroup[] = [];
    const itemsById = new Map((order.items ?? []).map((item) => [item.id, item]));

    const unfulfilledItems: FulfillmentGroupItem[] = [];
    const digitalItems: FulfillmentGroupItem[] = [];
    const refundedItems: FulfillmentGroupItem[] = [];

    for (const item of order.items ?? []) {
        const breakdown = itemBreakdown[item.id];
        if (!breakdown) continue;

        if (breakdown.unfulfilled > 0) {
            const target = item.requires_shipping ? unfulfilledItems : digitalItems;
            target.push({ orderItem: item, quantity: breakdown.unfulfilled });
        }
        if (breakdown.refunded > 0) {
            refundedItems.push({ orderItem: item, quantity: breakdown.refunded });
        }
    }

    if (unfulfilledItems.length > 0) {
        groups.push({ type: 'unfulfilled', items: unfulfilledItems });
    }

    const shippedRefundedRemaining = new Map<number, number>();
    for (const item of order.items ?? []) {
        const b = itemBreakdown[item.id];
        if (b && b.total_refunded > b.refunded) {
            shippedRefundedRemaining.set(item.id, b.total_refunded - b.refunded);
        }
    }

    for (const shipment of order.shipments ?? []) {
        const items: FulfillmentGroupItem[] = [];
        for (const si of shipment.items) {
            const orderItem = itemsById.get(si.order_item_id);
            if (orderItem) {
                const remaining = shippedRefundedRemaining.get(si.order_item_id) ?? 0;
                const shippedRefunded = remaining > 0 ? Math.min(remaining, si.quantity) : undefined;
                if (shippedRefunded) {
                    shippedRefundedRemaining.set(si.order_item_id, remaining - shippedRefunded);
                }
                items.push({ orderItem, quantity: si.quantity, shippedRefunded });
            }
        }
        if (items.length > 0) {
            groups.push({ type: 'fulfilled', shipment, items });
        }
    }

    if (digitalItems.length > 0) {
        groups.push({ type: 'digital', items: digitalItems });
    }

    if (refundedItems.length > 0) {
        groups.push({ type: 'refunded', items: refundedItems });
    }

    if (groups.length === 0) {
        const physicalItems: FulfillmentGroupItem[] = [];
        const digitalFallbackItems: FulfillmentGroupItem[] = [];
        for (const item of order.items ?? []) {
            const target = item.requires_shipping ? physicalItems : digitalFallbackItems;
            target.push({ orderItem: item, quantity: item.quantity });
        }
        if (physicalItems.length > 0) {
            groups.push({ type: 'unfulfilled', items: physicalItems });
        }
        if (digitalFallbackItems.length > 0) {
            groups.push({ type: 'digital', items: digitalFallbackItems });
        }
    }

    return groups;
}
