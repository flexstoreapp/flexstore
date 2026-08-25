import {
    ArrowLeftRightIcon,
    BanIcon,
    CircleCheckIcon,
    CircleDotIcon,
    CreditCardIcon,
    LockIcon,
    LockOpenIcon,
    MailIcon,
    MessageSquareTextIcon,
    PackageCheckIcon,
    PackageXIcon,
    PencilIcon,
    RotateCcwIcon,
    TriangleAlertIcon,
    TruckIcon,
    Undo2Icon,
} from 'lucide-react';
import { AnimatePresence, m } from 'motion/react';
import type { ReactNode } from 'react';

import { ActivityTimelineItem } from '@/components/admin/activity-timeline-item';
import { ActivityContent } from '@/components/admin/order/activity-content';
import type { Order, OrderActivity } from '@/types';

function getIcon(activity: OrderActivity): ReactNode {
    switch (activity.type) {
        case 'fulfillment_canceled':
            return <PackageXIcon className="size-3" />;
        case 'fulfillment_status_changed':
            if (activity.metadata?.to_status === 'on_hold') return <LockIcon className="size-3" />;
            if (activity.metadata?.from_status === 'on_hold') return <LockOpenIcon className="size-3" />;
            return <ArrowLeftRightIcon className="size-3" />;
        case 'items_fulfilled':
            return <PackageCheckIcon className="size-3" />;
        case 'email_resent':
            return <MailIcon className="size-3" />;
        case 'order_canceled':
            return <BanIcon className="size-3" />;
        case 'note_added':
            return <MessageSquareTextIcon className="size-3" />;
        case 'order_edited':
            return <PencilIcon className="size-3" />;
        case 'payment_received':
        case 'payment_status_changed':
        case 'payment_voided':
            return <CreditCardIcon className="size-3" />;
        case 'refund_completed':
        case 'refund_failed':
        case 'refund_pending':
            return <RotateCcwIcon className="size-3 rtl:-scale-x-100" />;
        case 'tracking_updated':
            return <TruckIcon className="size-3" />;
        case 'delivery_updated':
            if (activity.metadata?.status === 'delivered') return <CircleCheckIcon className="size-3" />;
            if (activity.metadata?.status === 'failed') return <TriangleAlertIcon className="size-3" />;
            if (activity.metadata?.status === 'returned') return <Undo2Icon className="size-3 rtl:-scale-x-100" />;
            return <TruckIcon className="size-3" />;
        default:
            return <CircleDotIcon className="size-3" />;
    }
}

export function ActivityTimeline({ activities, order }: { activities: OrderActivity[]; order: Order }) {
    if (activities.length === 0) {
        return null;
    }

    return (
        <AnimatePresence initial={false}>
            {activities.map((activity, index) => (
                <m.div
                    key={`activity-${activity.id}`}
                    initial={{ opacity: 0, height: 0, overflow: 'hidden' }}
                    animate={{ opacity: 1, height: 'auto', overflow: 'visible' }}
                    transition={{ duration: 0.2, ease: 'easeOut' }}
                >
                    <ActivityTimelineItem icon={getIcon(activity)} isLast={index === activities.length - 1}>
                        <ActivityContent activity={activity} order={order} />
                    </ActivityTimelineItem>
                </m.div>
            ))}
        </AnimatePresence>
    );
}
