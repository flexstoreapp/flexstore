import { useId, useRef, type ReactNode } from 'react';
import { createPortal } from 'react-dom';

import { CloseButton } from '@/components/storefront/close-button';
import {
    useDialogDismiss,
    useDragToClose,
    useFocusTrap,
    useViewportSizedOverlay,
} from '@/hooks/storefront/use-storefront-dialog';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface DialogProps {
    open: boolean;
    onClose: () => void;
    title: string;
    children: ReactNode;
    autoFocus?: boolean;
}

export function Dialog({ open, onClose, title, children, autoFocus = false }: DialogProps) {
    const titleId = useId();
    const overlayRef = useRef<HTMLDivElement>(null);
    const panelRef = useRef<HTMLDivElement>(null);
    const bodyRef = useRef<HTMLDivElement>(null);

    useFocusTrap(open, autoFocus, panelRef);
    useViewportSizedOverlay(open, overlayRef);
    useDialogDismiss(open, onClose);
    useDragToClose(open, onClose, panelRef, bodyRef);

    if (typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div aria-hidden={!open} className={cn('fixed inset-0 z-50', !open && 'pointer-events-none')}>
            <button
                type="button"
                aria-hidden="true"
                tabIndex={-1}
                onClick={onClose}
                className={cn(
                    'absolute inset-0 cursor-default bg-ink/50 transition-opacity duration-(--duration-slow)',
                    open ? 'opacity-100' : 'opacity-0',
                )}
            />
            <div
                ref={overlayRef}
                className="pointer-events-none absolute inset-x-0 top-0 flex h-[100dvh] items-end justify-center sm:items-center"
            >
                <div
                    ref={panelRef}
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby={titleId}
                    tabIndex={-1}
                    className={cn(
                        'relative z-10 flex max-h-full w-full max-w-[560px] flex-col overflow-hidden rounded-t-lg bg-surface transition duration-(--duration-slow) ease-out-quart sm:max-h-[90vh] sm:rounded-lg',
                        open
                            ? 'pointer-events-auto translate-y-0 opacity-100 sm:scale-100'
                            : 'pointer-events-none translate-y-full opacity-100 sm:translate-y-0 sm:scale-95 sm:opacity-0',
                    )}
                >
                    <div className="flex shrink-0 items-center justify-between border-b border-line px-6 py-4">
                        <span
                            aria-hidden="true"
                            className="absolute inset-x-0 top-2 mx-auto h-1 w-10 rounded-full bg-line-strong sm:hidden"
                        />
                        <h2 id={titleId} className="m-0 text-lg font-semibold text-ink">
                            {title}
                        </h2>
                        <CloseButton onClick={onClose} aria-label={__('Close')} className="-me-2" />
                    </div>
                    <div ref={bodyRef} className="overflow-auto px-6 pt-5 pb-6">
                        {children}
                    </div>
                </div>
            </div>
        </div>,
        document.body,
    );
}
