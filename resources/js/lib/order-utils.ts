import type { FulfillmentStatus, PaymentStatus, RefundStatus } from '@/types';

import { __ } from './i18n';

export function getStatusLabel(status: FulfillmentStatus | PaymentStatus | RefundStatus): string {
    const statusLabels: Record<FulfillmentStatus | PaymentStatus | RefundStatus, string> = {
        unfulfilled: __('Unfulfilled'),
        in_progress: __('In progress'),
        fulfilled: __('Fulfilled'),
        on_hold: __('On hold'),
        canceled: __('Canceled'),
        completed: __('Completed'),
        pending: __('Pending'),
        unpaid: __('Unpaid'),
        partially_paid: __('Partially paid'),
        paid: __('Paid'),
        refunded: __('Refunded'),
        partially_refunded: __('Partially refunded'),
        failed: __('Failed'),
    };

    return statusLabels[status] ?? status;
}
