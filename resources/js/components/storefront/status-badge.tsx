import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export type StatusTone = 'neutral' | 'info' | 'success' | 'warning' | 'error';

const TONE_STYLES: Record<StatusTone, string> = {
    neutral: 'text-muted bg-ink/10',
    info: 'text-primary bg-primary-tint',
    success: 'text-success bg-success/10',
    warning: 'text-orange bg-orange/10',
    error: 'text-error bg-error/10',
};

interface StatusBadgeProps {
    tone: StatusTone;
    children: ReactNode;
    className?: string;
}

export function StatusBadge({ tone, children, className }: StatusBadgeProps) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-xs px-2.5 py-1 text-2xs font-bold tracking-label uppercase',
                TONE_STYLES[tone],
                className,
            )}
        >
            {children}
        </span>
    );
}
