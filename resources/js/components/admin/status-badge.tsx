import { type ComponentProps, type ReactNode } from 'react';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type {
    CheckoutSessionStatus,
    PaymentStatus,
    FulfillmentStatus,
    ReviewStatus,
    RefundStatus,
    TransactionStatus,
} from '@/types';

type ActivationStatus = 'active' | 'inactive' | 'scheduled' | 'expired';

type Status =
    | PaymentStatus
    | FulfillmentStatus
    | ReviewStatus
    | RefundStatus
    | TransactionStatus
    | CheckoutSessionStatus
    | ActivationStatus;

type StatusTone = 'info' | 'success' | 'warning' | 'error' | 'neutral';

const TONE_CLASSES: Record<StatusTone, string> = {
    info: 'border-blue-400/40 bg-blue-400/10 text-blue-600 dark:text-blue-400',
    success: 'border-emerald-400/40 bg-emerald-400/10 text-emerald-600 dark:text-emerald-400',
    warning: 'border-amber-400/40 bg-amber-400/10 text-amber-600 dark:text-amber-400',
    error: 'border-red-400/40 bg-red-400/10 text-red-600 dark:text-red-400',
    neutral: 'border-neutral-400/40 bg-neutral-400/10 text-neutral-600 dark:text-neutral-400',
};

function getStatusTone(status: Status | null): StatusTone {
    switch (status?.toLowerCase()) {
        case 'unpaid':
        case 'pending':
        case 'upcoming':
        case 'in_progress':
        case 'requested':
        case 'received':
            return 'info';
        case 'paid':
        case 'fulfilled':
        case 'active':
        case 'approved':
        case 'completed':
        case 'success':
            return 'success';
        case 'unfulfilled':
        case 'partially_paid':
        case 'partially_refunded':
        case 'on_hold':
            return 'warning';
        case 'canceled':
        case 'declined':
        case 'failed':
        case 'rejected':
        case 'inactive':
            return 'error';
        default:
            return 'neutral';
    }
}

type StatusBadgeProps = ComponentProps<typeof Badge> & { children: ReactNode } & (
        { status: Status; tone?: never } | { status?: never; tone: StatusTone }
    );

export function StatusBadge({ status, tone, children, className, ...props }: StatusBadgeProps) {
    return (
        <Badge
            variant="outline"
            className={cn(TONE_CLASSES[tone ?? getStatusTone(status ?? null)], className)}
            {...props}
        >
            {children}
        </Badge>
    );
}
