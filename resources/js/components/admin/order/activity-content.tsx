import type { ReactNode } from 'react';

import { ActivityTimestamp } from '@/components/admin/activity-timestamp';
import { ActivityCancellationEntry } from '@/components/admin/order/activity-cancellation-entry';
import { ActivityEditEntry } from '@/components/admin/order/activity-edit-entry';
import { ActivityFulfillmentEntry } from '@/components/admin/order/activity-fulfillment-entry';
import { ActivityNoteCard } from '@/components/admin/order/activity-note-card';
import { ActivityRefundEntry } from '@/components/admin/order/activity-refund-entry';
import { ActivityTransactionEntry } from '@/components/admin/order/activity-transaction-entry';
import { ExpandableActivityRow } from '@/components/admin/order/expandable-activity-row';
import { ResendEmailButton } from '@/components/admin/order/resend-email-button';
import type { ResendPayload } from '@/components/admin/order/use-resend-notification';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useFormatMoney } from '@/hooks/use-format-money';
import { getActivityLabel } from '@/lib/activity-utils';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Order, OrderActivity } from '@/types';

function getResend(activity: OrderActivity): { title: string; payload: ResendPayload } | null {
    const metadata = activity.metadata ?? {};

    switch (activity.type) {
        case 'order_placed':
            return { title: __('Resend order confirmation?'), payload: { type: 'order_confirmed' } };
        case 'order_edited':
            return { title: __('Resend order update?'), payload: { type: 'order_updated' } };
        case 'order_canceled':
            return { title: __('Resend cancellation email?'), payload: { type: 'order_canceled' } };
        case 'items_fulfilled': {
            const shipmentId = metadata.shipment_id as number | undefined;
            return shipmentId
                ? {
                      title: __('Resend shipping confirmation?'),
                      payload: { type: 'order_fulfilled', shipment_id: shipmentId },
                  }
                : null;
        }
        case 'tracking_updated': {
            const shipmentId = metadata.shipment_id as number | undefined;
            return shipmentId
                ? {
                      title: __('Resend tracking update?'),
                      payload: { type: 'tracking_updated', shipment_id: shipmentId },
                  }
                : null;
        }
        case 'refund_completed': {
            const refundId = metadata.refund_id as number | undefined;
            return refundId
                ? { title: __('Resend refund email?'), payload: { type: 'order_refunded', refund_id: refundId } }
                : null;
        }
        default:
            return null;
    }
}

function ActivityEntry({ activity, order, resend }: { activity: OrderActivity; order: Order; resend: ReactNode }) {
    const { formatMoney } = useFormatMoney();

    if (activity.type === 'fulfillment_canceled' || activity.type === 'items_fulfilled') {
        return <ActivityFulfillmentEntry activity={activity} resend={resend} />;
    }

    if (activity.type === 'order_canceled') {
        return <ActivityCancellationEntry activity={activity} resend={resend} />;
    }

    if (activity.type === 'note_added') {
        return <ActivityNoteCard activity={activity} />;
    }

    if (activity.type === 'order_edited') {
        return <ActivityEditEntry activity={activity} resend={resend} />;
    }

    if (activity.type === 'payment_received' || activity.type === 'payment_voided') {
        const transactionId = activity.metadata?.transaction_id as number | undefined;
        const transaction = transactionId ? order.transactions?.find((t) => t.id === transactionId) : undefined;

        if (transaction) {
            return <ActivityTransactionEntry transaction={transaction} />;
        }
    }

    if (
        activity.type === 'refund_completed' ||
        activity.type === 'refund_failed' ||
        activity.type === 'refund_pending'
    ) {
        const refundId = activity.metadata?.refund_id as number | undefined;
        const refund = refundId ? order.refunds?.find((r) => r.id === refundId) : undefined;
        const transaction = refund ? order.transactions?.find((t) => t.order_refund_id === refund.id) : undefined;

        if (refund) {
            return (
                <ActivityRefundEntry
                    refund={refund}
                    currencyCode={order.currency_code}
                    transaction={transaction}
                    resend={resend}
                />
            );
        }
    }

    if (activity.type === 'payment_request_sent') {
        const amount = activity.metadata?.amount as string | undefined;

        return (
            <div className="flex items-start justify-between gap-3">
                <p className="flex flex-wrap items-center gap-2 pt-0.5 text-sm text-foreground">
                    <span>{getActivityLabel(activity)}</span>
                    {amount && <span>{formatMoney(amount, order.currency_code)}</span>}
                </p>
                <ActivityTimestamp date={activity.created_at} />
            </div>
        );
    }

    return (
        <ExpandableActivityRow label={getActivityLabel(activity)} date={activity.created_at} expandable={!!resend}>
            {resend}
        </ExpandableActivityRow>
    );
}

export function ActivityContent({ activity, order }: { activity: OrderActivity; order: Order }) {
    const { hasPermission } = usePermissions();
    const descriptor = hasPermission(Permission.OrdersManage) ? getResend(activity) : null;
    const resend = descriptor ? (
        <ResendEmailButton order={order} title={descriptor.title} payload={descriptor.payload} />
    ) : null;

    return <ActivityEntry activity={activity} order={order} resend={resend} />;
}
