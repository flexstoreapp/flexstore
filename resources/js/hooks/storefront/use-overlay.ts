import { useEffect, useRef, type RefObject } from 'react';

const FOCUSABLE = 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

let openOverlays = 0;

function visibleFocusables(panel: HTMLElement): HTMLElement[] {
    return Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE)).filter((el) => {
        const rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0;
    });
}

/**
 * Dialog behaviour for an overlay panel: Escape to close, body scroll lock,
 * focus trap while open, and focus restore to the trigger on close.
 */
export function useOverlay(open: boolean, onClose: () => void): RefObject<HTMLElement | null> {
    const panelRef = useRef<HTMLElement | null>(null);
    const onCloseRef = useRef(onClose);

    useEffect(() => {
        onCloseRef.current = onClose;
    }, [onClose]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const panel = panelRef.current;
        const previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        openOverlays += 1;
        document.body.classList.add('overflow-hidden');

        const handleKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                event.stopPropagation();
                onCloseRef.current();
                return;
            }

            if (event.key !== 'Tab' || !panel) {
                return;
            }

            const focusables = visibleFocusables(panel);

            if (focusables.length === 0) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;

            if (event.shiftKey && (active === first || !panel.contains(active))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (active === last || !panel.contains(active))) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);

            openOverlays -= 1;

            if (openOverlays === 0) {
                document.body.classList.remove('overflow-hidden');
            }

            previouslyFocused?.focus({ preventScroll: true });
        };
    }, [open]);

    return panelRef;
}
