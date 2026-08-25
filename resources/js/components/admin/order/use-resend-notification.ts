import { router } from '@inertiajs/react';

import ResendOrderNotificationController from '@/actions/App/Http/Controllers/Admin/ResendOrderNotificationController';
import { useConfirm } from '@/components/admin/confirm';
import { __ } from '@/lib/i18n';
import type { Order } from '@/types';

export type OrderEmailType =
    'order_confirmed' | 'order_fulfilled' | 'tracking_updated' | 'order_refunded' | 'order_canceled' | 'order_updated';

export interface ResendPayload {
    type: OrderEmailType;
    shipment_id?: number;
    refund_id?: number;
    [key: string]: OrderEmailType | number | undefined;
}

export function useResendNotification(order: Order) {
    const { confirm } = useConfirm();

    return (title: string, payload: ResendPayload) => {
        confirm({
            title,
            description: order.customer_email
                ? __('A copy of this email will be sent to :email.', { email: order.customer_email })
                : __('A copy of this email will be sent to the customer.'),
            confirmLabel: __('Resend'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(ResendOrderNotificationController.url(order), payload, {
                        preserveScroll: true,
                        onFinish: () => resolve(),
                    });
                }),
        });
    };
}
