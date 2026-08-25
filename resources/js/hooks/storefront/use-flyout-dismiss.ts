import { useRef, type MouseEvent, type RefObject } from 'react';

export function useFlyoutDismiss<T extends HTMLElement>(): {
    ref: RefObject<T | null>;
    onClickCapture: (event: MouseEvent<T>) => void;
} {
    const ref = useRef<T>(null);

    const onClickCapture = (event: MouseEvent<T>) => {
        const target = (event.target as HTMLElement).closest('a[href],button');

        if (!target || target.closest('[aria-haspopup]')) {
            return;
        }

        if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
        }

        const container = ref.current;

        if (!container) {
            return;
        }

        container.style.pointerEvents = 'none';
        window.addEventListener('pointermove', () => (container.style.pointerEvents = ''), { once: true });
    };

    return { ref, onClickCapture };
}
