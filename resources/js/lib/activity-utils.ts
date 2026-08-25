import { __ } from '@/lib/i18n';
import type { OrderActivity } from '@/types';

export function getActivityLabel(activity: OrderActivity) {
    const metadata = activity.metadata ?? {};

    switch (activity.type) {
        case 'email_resent': {
            const emailType = metadata.email_type as string | undefined;
            switch (emailType) {
                case 'order_confirmed':
                    return __('Order confirmation email resent');
                case 'order_fulfilled':
                    return __('Shipping confirmation email resent');
                case 'tracking_updated':
                    return __('Tracking update email resent');
                case 'order_refunded':
                    return __('Refund email resent');
                case 'order_canceled':
                    return __('Cancellation email resent');
                case 'order_updated':
                    return __('Order update email resent');
                default:
                    return __('Email resent');
            }
        }
        case 'delivery_updated': {
            const deliveryStatus = metadata.status as string | undefined;

            if (metadata.return_id != null) {
                switch (deliveryStatus) {
                    case 'pending':
                        return __('Return shipment pending');
                    case 'picked_up':
                        return __('Return picked up by carrier');
                    case 'in_transit':
                        return __('Return in transit');
                    case 'out_for_delivery':
                        return __('Return out for delivery');
                    case 'delivered':
                        return __('Return delivered');
                    case 'failed':
                        return __('Return delivery failed');
                    default:
                        return __('Return delivery updated');
                }
            }

            switch (deliveryStatus) {
                case 'pending':
                    return __('Shipment pending');
                case 'picked_up':
                    return __('Picked up by carrier');
                case 'in_transit':
                    return __('In transit');
                case 'out_for_delivery':
                    return __('Out for delivery');
                case 'delivered':
                    return __('Delivered');
                case 'failed':
                    return __('Delivery failed');
                case 'returned':
                    return __('Returned to sender');
                default:
                    return __('Delivery updated');
            }
        }
        case 'fulfillment_canceled':
            return __('Fulfillment canceled');
        case 'fulfillment_status_changed': {
            const from = metadata.from_status as string | undefined;
            const to = metadata.to_status as string | undefined;
            if (to === 'on_hold') return __('Order put on hold');
            if (to === 'fulfilled') return __('Order fulfilled');
            if (to === 'in_progress') return __('Order marked as in progress');
            if (from === 'on_hold') return __('Order hold released');
            if (to === 'unfulfilled') return __('Order marked as unfulfilled');
            return __('Fulfillment updated');
        }
        case 'items_fulfilled':
            return __('Items fulfilled');
        case 'order_canceled':
            return __('Order canceled');
        case 'order_edited':
            return __('Order edited');
        case 'order_placed':
            return __('Order placed');
        case 'payment_received':
            return __('Payment received');
        case 'payment_request_sent':
            return __('Payment request sent');
        case 'payment_status_changed': {
            const toPayment = metadata.to_status as string | undefined;
            if (toPayment === 'refunded') return __('Fully refunded');
            if (toPayment === 'partially_refunded') return __('Partially refunded');
            if (toPayment === 'failed') return __('Payment failed');
            if (toPayment === 'canceled') return __('Payment canceled');
            return __('Payment updated');
        }
        case 'payment_voided':
            return __('Payment voided');
        case 'refund_completed':
            return __('Refund completed');
        case 'refund_failed':
            return __('Refund failed');
        case 'refund_pending':
            return __('Refund pending');
        case 'shipment_void_failed':
            return __('Shipping label could not be voided');
        case 'tracking_updated':
            return __('Tracking updated');
        default:
            return activity.type;
    }
}
