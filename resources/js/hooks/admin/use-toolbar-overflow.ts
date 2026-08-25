import { useCallback, useLayoutEffect, useRef, useState } from 'react';

const GAP = 6;
const OVERFLOW_TRIGGER_WIDTH = 36;

export function useToolbarOverflow(count: number) {
    const containerRef = useRef<HTMLDivElement>(null);
    const controlsRef = useRef<HTMLDivElement>(null);
    const itemsRef = useRef<(HTMLDivElement | null)[]>([]);
    const widthsRef = useRef<number[] | null>(null);
    const [visibleCount, setVisibleCount] = useState(count);

    const setItemRef = useCallback(
        (index: number) => (node: HTMLDivElement | null) => {
            itemsRef.current[index] = node;
        },
        [],
    );

    useLayoutEffect(() => {
        const container = containerRef.current;

        if (!container) {
            return;
        }

        const measure = () => {
            if (!widthsRef.current) {
                const measured = itemsRef.current
                    .slice(0, count)
                    .map((node) => node?.getBoundingClientRect().width ?? 0);

                if (measured.length < count || measured.some((width) => width === 0)) {
                    return;
                }

                widthsRef.current = measured;
            }

            const widths = widthsRef.current;
            const styles = getComputedStyle(container);
            const padding = parseFloat(styles.paddingLeft) + parseFloat(styles.paddingRight);
            const controls = controlsRef.current?.getBoundingClientRect().width ?? 0;
            const available = container.clientWidth - padding - controls - GAP;

            const countThatFits = (reserved: number) => {
                let used = 0;
                let fitted = 0;

                for (const width of widths) {
                    const next = used + width + (fitted > 0 ? GAP : 0);

                    if (next > available - reserved) {
                        break;
                    }

                    used = next;
                    fitted += 1;
                }

                return fitted;
            };

            const unreserved = countThatFits(0);

            setVisibleCount(unreserved === widths.length ? unreserved : countThatFits(OVERFLOW_TRIGGER_WIDTH + GAP));
        };

        measure();

        const observer = new ResizeObserver(measure);

        observer.observe(container);

        return () => observer.disconnect();
    }, [count]);

    return { containerRef, controlsRef, setItemRef, visibleCount };
}
