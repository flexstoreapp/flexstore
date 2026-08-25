import { type CSSProperties } from 'react';

import { cn } from '@/lib/utils';

export type DropEdge = 'top' | 'bottom' | 'start' | 'end';

const edgeClasses: Record<DropEdge, string> = {
    top: '-start-1.5 end-0 -translate-y-1/2 items-center',
    bottom: '-start-1.5 end-0 translate-y-1/2 items-center',
    start: 'inset-y-0 -translate-x-1/2 flex-col items-center rtl:translate-x-1/2',
    end: 'inset-y-0 translate-x-1/2 flex-col items-center rtl:-translate-x-1/2',
};

function edgeOffsetStyle(edge: DropEdge, offset: string): CSSProperties {
    switch (edge) {
        case 'top':
            return { top: offset };
        case 'bottom':
            return { bottom: offset };
        case 'start':
            return { insetInlineStart: offset };
        case 'end':
            return { insetInlineEnd: offset };
    }
}

export function DropIndicator({ edge, gap = 2 }: { edge: DropEdge; gap?: number }) {
    const isHorizontalLine = edge === 'top' || edge === 'bottom';

    return (
        <div
            className={cn('pointer-events-none absolute z-10 flex', edgeClasses[edge])}
            style={edgeOffsetStyle(edge, `-${gap * 2}px`)}
            aria-hidden
        >
            <div className="size-2 shrink-0 rounded-full bg-primary" />
            <div className={cn('flex-1 rounded-full bg-primary', isHorizontalLine ? 'h-0.5' : 'w-0.5')} />
        </div>
    );
}
