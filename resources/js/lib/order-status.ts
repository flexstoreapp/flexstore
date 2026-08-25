import type { StatusTone } from '@/components/storefront/status-badge';
import { __ } from '@/lib/i18n';
import type { FulfillmentStatus } from '@/types';

export function orderStatus(order: { fulfillment_status: FulfillmentStatus; canceled_at: string | null }): {
    tone: StatusTone;
    label: string;
} {
    if (order.canceled_at !== null) {
        return { tone: 'error', label: __('Canceled') };
    }

    switch (order.fulfillment_status) {
        case 'fulfilled':
            return { tone: 'success', label: __('Fulfilled') };
        case 'in_progress':
            return { tone: 'info', label: __('In progress') };
        case 'on_hold':
            return { tone: 'neutral', label: __('On hold') };
        default:
            return { tone: 'warning', label: __('Unfulfilled') };
    }
}
