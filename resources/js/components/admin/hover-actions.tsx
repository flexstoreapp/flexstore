import { type PropsWithChildren } from 'react';

import { cn } from '@/lib/utils';

type HoverActionsProps = PropsWithChildren<{
    className?: string;
}>;

export function HoverActions({ children, className }: HoverActionsProps) {
    return (
        <div
            className={cn(
                'flex items-center gap-1 transition-opacity duration-150',
                '[@media(hover:hover)]:pointer-events-none [@media(hover:hover)]:opacity-0',
                '[@media(hover:hover)]:group-hover/item:pointer-events-auto [@media(hover:hover)]:group-hover/item:opacity-100',
                className,
            )}
        >
            {children}
        </div>
    );
}
