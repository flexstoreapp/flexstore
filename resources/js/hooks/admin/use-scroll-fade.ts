import { useCallback, useEffect, useRef, useState } from 'react';

export function useScrollFade<T extends HTMLElement>(axis: 'x' | 'y' = 'y') {
    const scrollerRef = useRef<T | null>(null);
    const [edges, setEdges] = useState({ atStart: true, atEnd: true });

    const measure = useCallback(() => {
        const el = scrollerRef.current;

        if (!el) {
            return;
        }

        const max = axis === 'x' ? el.scrollWidth - el.clientWidth : el.scrollHeight - el.clientHeight;
        let position = axis === 'x' ? el.scrollLeft : el.scrollTop;

        if (axis === 'x' && getComputedStyle(el).direction === 'rtl') {
            position = -position;
        }

        const atStart = position <= 1;
        const atEnd = position >= max - 1;

        setEdges((previous) =>
            previous.atStart === atStart && previous.atEnd === atEnd ? previous : { atStart, atEnd },
        );
    }, [axis]);

    useEffect(() => {
        measure();

        const el = scrollerRef.current;

        if (!el) {
            return;
        }

        const observer = new ResizeObserver(measure);
        observer.observe(el);

        if (el.firstElementChild) {
            observer.observe(el.firstElementChild);
        }

        return () => observer.disconnect();
    }, [measure]);

    return {
        scrollerRef,
        onScroll: measure,
        fadeAttributes: {
            'data-at-start': edges.atStart || undefined,
            'data-at-end': edges.atEnd || undefined,
        },
    };
}
