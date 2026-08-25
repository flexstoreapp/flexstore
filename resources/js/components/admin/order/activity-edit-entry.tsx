import type { ReactNode } from 'react';

import { getActivityLabel } from '@/lib/activity-utils';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { OrderActivity } from '@/types';

import { ExpandableActivityRow } from './expandable-activity-row';

interface Change {
    type: string;
    title?: Record<string, string>;
    variant?: string;
    quantity?: number;
    from?: unknown;
    to?: unknown;
}

function formatChangeLine(change: Change): string {
    const title = change.title ? getTranslation(change.title) : '';
    const label = change.variant ? `${title} (${change.variant})` : title;

    switch (change.type) {
        case 'item_added':
            return __(':item added', { item: `${label} \u00d7 ${change.quantity}` });
        case 'item_removed':
            return __(':item removed', { item: `${label} \u00d7 ${change.quantity}` });
        case 'item_quantity_changed':
            return __(':item quantity changed from :from to :to', {
                item: label,
                from: String(change.from),
                to: String(change.to),
            });
        case 'shipping_changed':
            return formatFromTo(__('Shipping method'), change);
        case 'payment_gateway_changed':
            return formatFromTo(__('Payment method'), change);
        case 'coupon_changed':
            return formatFromTo(__('Coupon'), change);
        case 'contact_email_changed':
            return formatFromTo(__('Contact email'), change);
        case 'customer_changed':
            return __('Customer changed');
        default:
            return change.type;
    }
}

function formatFromTo(label: string, change: Change): string {
    const from =
        typeof change.from === 'object' && change.from !== null
            ? getTranslation(change.from as Record<string, string>)
            : change.from;
    const to =
        typeof change.to === 'object' && change.to !== null
            ? getTranslation(change.to as Record<string, string>)
            : change.to;

    if (!from && to) return __(':label set to :to', { label, to: String(to) });
    if (from && !to) return __(':label removed (:from)', { label, from: String(from) });
    return __(':label changed from :from to :to', { label, from: String(from), to: String(to) });
}

export function ActivityEditEntry({ activity, resend }: { activity: OrderActivity; resend?: ReactNode }) {
    const changes = (activity.metadata?.changes ?? []) as Change[];
    const hasChanges = changes.length > 0;

    return (
        <ExpandableActivityRow
            label={getActivityLabel(activity)}
            date={activity.created_at}
            expandable={hasChanges || !!resend}
        >
            <div className="space-y-2">
                {hasChanges && (
                    <ul className="space-y-1">
                        {changes.map((change, index) => (
                            <li key={index} className="text-xs text-muted-foreground">
                                {formatChangeLine(change)}
                            </li>
                        ))}
                    </ul>
                )}
                {resend}
            </div>
        </ExpandableActivityRow>
    );
}
