import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface SectionFrameProps {
    cols: string;
    className?: string;
    children: ReactNode;
}

export function SectionFrame({ cols, className, children }: SectionFrameProps) {
    return (
        <div
            className={cn(
                'relative overflow-hidden rounded-md bg-surface after:pointer-events-none after:absolute after:inset-0 after:rounded-md after:border after:border-line',
                className,
            )}
        >
            <div className={cn('grid', cols)}>{children}</div>
        </div>
    );
}
