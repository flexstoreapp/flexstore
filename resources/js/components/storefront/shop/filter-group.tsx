import { useLayoutEffect, useRef, useState, type ReactNode } from 'react';

import { ScrollFade } from '@/components/scroll-fade';

interface FilterGroupProps {
    title: string;
    children: ReactNode;
    visibleCount?: number;
}

export function FilterGroup({ title, children, visibleCount }: FilterGroupProps) {
    const listRef = useRef<HTMLDivElement>(null);
    const [maxHeight, setMaxHeight] = useState<number>();

    useLayoutEffect(() => {
        const list = listRef.current;

        if (!list || !visibleCount) {
            setMaxHeight(undefined);

            return;
        }

        const measure = () => {
            const rows = Array.from(list.children) as HTMLElement[];

            if (rows.length <= visibleCount) {
                setMaxHeight(undefined);

                return;
            }

            const last = rows[visibleCount - 1];

            setMaxHeight(last.offsetTop - list.offsetTop + last.offsetHeight);
        };

        measure();

        const observer = new ResizeObserver(measure);
        observer.observe(list);

        return () => observer.disconnect();
    }, [visibleCount, children]);

    const list = (
        <div ref={listRef} className="flex flex-col gap-1.5 pe-3 lg:gap-2.5">
            {children}
        </div>
    );

    return (
        <div className="mb-5 border-b border-line pb-5 last:mb-0 last:border-b-0 last:pb-0">
            <div className="mb-3 text-sm font-bold tracking-label text-ink uppercase">{title}</div>
            {!visibleCount ? (
                list
            ) : (
                <ScrollFade
                    className="[--scroll-fade-color:var(--color-surface)] lg:[--scroll-fade-color:var(--color-page)]"
                    scrollerClassName="overflow-y-auto"
                    scrollerStyle={{ maxHeight }}
                >
                    {list}
                </ScrollFade>
            )}
        </div>
    );
}
