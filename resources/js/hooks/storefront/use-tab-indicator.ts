import { type KeyboardEvent, useEffect, useRef, useState } from 'react';

export function useTabIndicator(activeIndex: number, count: number, onChange: (index: number) => void) {
    const tabRefs = useRef<(HTMLButtonElement | null)[]>([]);
    const [indicator, setIndicator] = useState({ left: 0, width: 0 });

    useEffect(() => {
        const update = () => {
            const button = tabRefs.current[activeIndex];
            if (button) {
                setIndicator({ left: button.offsetLeft, width: button.offsetWidth });
            }
        };

        update();
        window.addEventListener('resize', update);

        return () => window.removeEventListener('resize', update);
    }, [activeIndex, count]);

    const onKeyDown = (event: KeyboardEvent<HTMLElement>) => {
        const rtl = getComputedStyle(event.currentTarget).direction === 'rtl';
        const forward = rtl ? -1 : 1;

        let next = -1;
        if (event.key === 'ArrowRight') next = (activeIndex + forward + count) % count;
        else if (event.key === 'ArrowLeft') next = (activeIndex - forward + count) % count;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = count - 1;

        if (next < 0) {
            return;
        }

        event.preventDefault();
        onChange(next);
        tabRefs.current[next]?.focus();
    };

    return { tabRefs, indicator, onKeyDown };
}
