import { useEffect, type RefObject } from 'react';

const CLOSE_THRESHOLD = 120;

const FOCUSABLE_SELECTOR =
    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function useFocusTrap(open: boolean, autoFocus: boolean, panelRef: RefObject<HTMLElement | null>) {
    useEffect(() => {
        const panel = panelRef.current;

        if (!open || !panel) {
            return;
        }

        const previouslyFocused = document.activeElement as HTMLElement | null;
        const focusables = () =>
            Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR)).filter(
                (element) => element.offsetParent !== null,
            );

        const initial = autoFocus ? panel.querySelector<HTMLElement>('input, select, textarea') : null;
        (initial ?? focusables()[0] ?? panel).focus({ preventScroll: true });

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key !== 'Tab') {
                return;
            }

            const items = focusables();

            if (items.length === 0) {
                event.preventDefault();
                panel.focus({ preventScroll: true });
                return;
            }

            const first = items[0];
            const last = items[items.length - 1];
            const active = document.activeElement;

            if (event.shiftKey && (active === first || !panel.contains(active))) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && (active === last || !panel.contains(active))) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            previouslyFocused?.focus({ preventScroll: true });
        };
    }, [open, autoFocus, panelRef]);
}

export function useViewportSizedOverlay(open: boolean, overlayRef: RefObject<HTMLElement | null>) {
    useEffect(() => {
        const viewport = window.visualViewport;
        const overlay = overlayRef.current;

        if (!open || !viewport || !overlay) {
            return;
        }

        const apply = () => {
            overlay.style.height = `${viewport.height}px`;
            overlay.style.transform = `translateY(${viewport.offsetTop}px)`;
        };

        apply();
        viewport.addEventListener('resize', apply);
        viewport.addEventListener('scroll', apply);

        return () => {
            viewport.removeEventListener('resize', apply);
            viewport.removeEventListener('scroll', apply);
            overlay.style.height = '';
            overlay.style.transform = '';
        };
    }, [open, overlayRef]);
}

export function useDialogDismiss(open: boolean, onClose: () => void) {
    useEffect(() => {
        if (!open) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('keydown', onKeyDown);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
        };
    }, [open, onClose]);
}

export function useDragToClose(
    open: boolean,
    onClose: () => void,
    panelRef: RefObject<HTMLElement | null>,
    bodyRef: RefObject<HTMLElement | null>,
) {
    useEffect(() => {
        const panel = panelRef.current;
        const body = bodyRef.current;

        if (!panel || !body) {
            return;
        }

        let startY = 0;
        let delta = 0;
        let active = false;

        const isBottomSheet = () => window.matchMedia('(max-width: 639px)').matches;

        const onStart = (event: TouchEvent) => {
            if (!open || !isBottomSheet() || body.scrollTop > 0) {
                active = false;
                return;
            }

            startY = event.touches[0].clientY;
            delta = 0;
            active = true;
            panel.style.transition = 'none';
        };

        const onMove = (event: TouchEvent) => {
            if (!active) {
                return;
            }

            delta = event.touches[0].clientY - startY;

            if (delta <= 0) {
                panel.style.transform = '';
                return;
            }

            panel.style.transform = `translateY(${delta}px)`;
            event.preventDefault();
        };

        const onEnd = () => {
            if (!active) {
                return;
            }

            active = false;
            panel.style.transition = '';
            panel.style.transform = '';

            if (delta > CLOSE_THRESHOLD) {
                onClose();
            }
        };

        panel.addEventListener('touchstart', onStart, { passive: true });
        panel.addEventListener('touchmove', onMove, { passive: false });
        panel.addEventListener('touchend', onEnd);
        panel.addEventListener('touchcancel', onEnd);

        return () => {
            panel.removeEventListener('touchstart', onStart);
            panel.removeEventListener('touchmove', onMove);
            panel.removeEventListener('touchend', onEnd);
            panel.removeEventListener('touchcancel', onEnd);
        };
    }, [open, onClose, panelRef, bodyRef]);
}
