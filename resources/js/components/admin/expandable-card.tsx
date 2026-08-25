import { ChevronDownIcon } from 'lucide-react';
import { m } from 'motion/react';
import React from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface ExpandableCardProps {
    isExpanded: boolean;
    onToggle: () => void;
    title: React.ReactNode;
    action?: React.ReactNode;
    children: React.ReactNode;
    className?: string;
    titleClassName?: string;
    contentClassName?: string;
    animationDuration?: number;
}

export function ExpandableCard({
    isExpanded,
    onToggle,
    title,
    action,
    children,
    className,
    titleClassName,
    contentClassName,
    animationDuration = 0.15,
}: ExpandableCardProps) {
    return (
        <div className={cn('rounded-lg border', className)}>
            <div className="flex cursor-pointer items-center justify-between px-4 py-3" onClick={onToggle}>
                <span className={cn('text-sm font-medium', titleClassName)}>{title}</span>
                <div className="flex items-center gap-2">
                    {action && <div className="-my-1 flex items-center">{action}</div>}
                    <Button type="button" variant="ghost" size="icon-sm">
                        <m.div animate={{ rotate: isExpanded ? 180 : 0 }} transition={{ duration: animationDuration }}>
                            <ChevronDownIcon className="size-3.5" />
                        </m.div>
                    </Button>
                </div>
            </div>

            <m.div
                initial={false}
                animate={{ height: isExpanded ? 'auto' : 0, opacity: isExpanded ? 1 : 0 }}
                transition={{ duration: animationDuration, ease: 'easeInOut' }}
                className="overflow-hidden"
            >
                <div className={cn('space-y-4 border-t p-4', contentClassName)}>{children}</div>
            </m.div>
        </div>
    );
}
