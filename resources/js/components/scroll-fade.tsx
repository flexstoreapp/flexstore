import type { CSSProperties, ReactNode } from 'react';

import { useScrollFade } from '@/hooks/admin/use-scroll-fade';
import { cn } from '@/lib/utils';

interface ScrollFadeProps {
    children: ReactNode;
    axis?: 'x' | 'y';
    className?: string;
    scrollerClassName?: string;
    scrollerStyle?: CSSProperties;
}

export function ScrollFade({ children, axis = 'y', className, scrollerClassName, scrollerStyle }: ScrollFadeProps) {
    const { scrollerRef, onScroll, fadeAttributes } = useScrollFade<HTMLDivElement>(axis);

    return (
        <div data-axis={axis} {...fadeAttributes} className={cn('scroll-fade', className)}>
            <div
                ref={scrollerRef}
                onScroll={onScroll}
                style={scrollerStyle}
                className={cn('scroll-fade-scroller', scrollerClassName)}
            >
                {children}
            </div>
        </div>
    );
}
