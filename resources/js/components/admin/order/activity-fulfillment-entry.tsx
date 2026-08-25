import type { ReactNode } from 'react';

import { getActivityLabel } from '@/lib/activity-utils';
import { getTranslation } from '@/lib/utils';
import type { OrderActivity } from '@/types';

import { ExpandableActivityRow } from './expandable-activity-row';

interface ActivityItem {
    title: Record<string, string>;
    variant?: string;
    quantity: number;
}

export function ActivityFulfillmentEntry({ activity, resend }: { activity: OrderActivity; resend?: ReactNode }) {
    const items = (activity.metadata?.items ?? []) as ActivityItem[];
    const hasItems = items.length > 0;

    return (
        <ExpandableActivityRow
            label={getActivityLabel(activity)}
            date={activity.created_at}
            expandable={hasItems || !!resend}
        >
            <div className="space-y-2">
                {hasItems && (
                    <ul className="space-y-1">
                        {items.map((item, index) => {
                            const title = getTranslation(item.title);
                            const label = item.variant ? `${title} (${item.variant})` : title;

                            return (
                                <li key={index} className="text-xs text-muted-foreground">
                                    {label} <span>&times; {item.quantity}</span>
                                </li>
                            );
                        })}
                    </ul>
                )}
                {resend}
            </div>
        </ExpandableActivityRow>
    );
}
