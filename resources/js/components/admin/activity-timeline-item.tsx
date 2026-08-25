import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface ActivityTimelineItemProps {
    icon: ReactNode;
    isLast: boolean;
    children: ReactNode;
}

export function ActivityTimelineItem({ icon, isLast, children }: ActivityTimelineItemProps) {
    return (
        <div className="flex gap-3">
            <div className="flex flex-col items-center">
                <div className="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    {icon}
                </div>
                {!isLast && <div className="my-1.5 w-px flex-1 bg-border" />}
            </div>

            <div className={cn('min-w-0 flex-1', !isLast && 'pb-6')}>{children}</div>
        </div>
    );
}
