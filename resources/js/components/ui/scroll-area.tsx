import { ScrollArea as ScrollAreaPrimitive } from "radix-ui"
import * as React from "react"

import { useScrollFade } from '@/hooks/admin/use-scroll-fade';
import { cn } from "@/lib/utils"

interface ScrollAreaProps extends React.ComponentPropsWithoutRef<typeof ScrollAreaPrimitive.Root> {
    scrollRegion?: boolean;
    fade?: boolean;
}

function ScrollArea({ className, children, scrollRegion, fade, ref, ...props }: ScrollAreaProps & { ref?: React.Ref<React.ElementRef<typeof ScrollAreaPrimitive.Root>> }) {
    const { scrollerRef, onScroll, fadeAttributes } = useScrollFade<HTMLDivElement>();

    return (
        <ScrollAreaPrimitive.Root
            ref={ref}
            className={cn("relative overflow-hidden", fade && "scroll-fade", className)}
            {...(fade ? fadeAttributes : {})}
            {...props}
        >
            <ScrollAreaPrimitive.Viewport
                ref={fade ? scrollerRef : undefined}
                onScroll={fade ? onScroll : undefined}
                className={cn("h-full w-full rounded-[inherit]", fade && "scroll-fade-scroller")}
                {...(scrollRegion && { 'scroll-region': '' })}
            >
                {children}
            </ScrollAreaPrimitive.Viewport>
            <ScrollBar />
            <ScrollAreaPrimitive.Corner />
        </ScrollAreaPrimitive.Root>
    );
}

function ScrollBar({ className, orientation = "vertical", ref, ...props }: React.ComponentPropsWithoutRef<typeof ScrollAreaPrimitive.ScrollAreaScrollbar> & { ref?: React.Ref<React.ElementRef<typeof ScrollAreaPrimitive.ScrollAreaScrollbar>> }) {
    return (
        <ScrollAreaPrimitive.ScrollAreaScrollbar
            ref={ref}
            orientation={orientation}
            forceMount
            className={cn(
                "flex touch-none select-none transition-opacity duration-200 data-[state=hidden]:pointer-events-none data-[state=hidden]:opacity-0",
                orientation === "vertical" &&
                "h-full w-2.5 border-s border-s-transparent p-px",
                orientation === "horizontal" &&
                "h-2.5 flex-col border-t border-t-transparent p-px",
                className
            )}
            {...props}
        >
            <ScrollAreaPrimitive.ScrollAreaThumb className="relative flex-1 rounded-full bg-border" />
        </ScrollAreaPrimitive.ScrollAreaScrollbar>
    );
}

export { ScrollArea, ScrollBar }
