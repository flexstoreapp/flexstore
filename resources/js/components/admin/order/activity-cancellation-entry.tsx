import { ChevronDownIcon } from 'lucide-react';
import { type ReactNode, useState } from 'react';

import { ActivityTimestamp } from '@/components/admin/activity-timestamp';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { OrderActivity } from '@/types';

import { CollapsibleSection } from './collapsible-section';

const reasonLabels: Record<string, string> = {
    customer_request: __('Customer request'),
    fraudulent: __('Fraudulent order'),
    inventory: __('Inventory issue'),
    other: __('Other'),
};

export function ActivityCancellationEntry({ activity, resend }: { activity: OrderActivity; resend?: ReactNode }) {
    const [expanded, setExpanded] = useState(false);
    const metadata = activity.metadata ?? {};
    const reason = metadata.cancellation_reason as string | undefined;
    const note = metadata.cancellation_note as string | undefined;
    const reasonLabel = reason ? reasonLabels[reason] : undefined;
    const label = reasonLabel ? __('Order canceled (:reason)', { reason: reasonLabel }) : __('Order canceled');

    return (
        <div>
            <div className="flex items-start justify-between gap-3">
                <div className="space-y-1.5">
                    {resend ? (
                        <button
                            type="button"
                            className="flex cursor-pointer items-center gap-2 pt-0.5 text-sm"
                            onClick={() => setExpanded(!expanded)}
                        >
                            <span>{label}</span>
                            <ChevronDownIcon
                                className={cn(
                                    'size-3.5 text-muted-foreground transition-transform duration-200',
                                    expanded && 'rotate-180',
                                )}
                            />
                        </button>
                    ) : (
                        <p className="pt-0.5 text-sm text-foreground">{label}</p>
                    )}
                    {note && <p className="text-xs text-muted-foreground">{note}</p>}
                </div>
                <ActivityTimestamp date={activity.created_at} />
            </div>

            {resend && (
                <CollapsibleSection open={expanded}>
                    <div className="pt-2">{resend}</div>
                </CollapsibleSection>
            )}
        </div>
    );
}
