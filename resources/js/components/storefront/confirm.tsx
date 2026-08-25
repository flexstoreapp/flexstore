import { createContext, useContext, useState, type PropsWithChildren } from 'react';

import { Button } from '@/components/storefront/button';
import { Dialog } from '@/components/storefront/dialog';
import { __ } from '@/lib/i18n';

interface ConfirmOptions {
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'primary' | 'error';
    action?: () => Promise<unknown>;
}

interface ConfirmState {
    open: boolean;
    options: ConfirmOptions | null;
    resolve: ((value: boolean) => void) | null;
}

interface ConfirmContextValue {
    confirm: (options: ConfirmOptions) => Promise<boolean>;
}

const ConfirmContext = createContext<ConfirmContextValue | null>(null);

export function useConfirm() {
    const context = useContext(ConfirmContext);

    if (!context) {
        throw new Error('useConfirm must be used within a ConfirmProvider');
    }

    return context;
}

export function ConfirmProvider({ children }: PropsWithChildren) {
    const [state, setState] = useState<ConfirmState>({ open: false, options: null, resolve: null });
    const [processing, setProcessing] = useState(false);

    const confirm = (options: ConfirmOptions) =>
        new Promise<boolean>((resolve) => setState({ open: true, options, resolve }));

    const close = (value: boolean) => {
        state.resolve?.(value);
        setState((current) => ({ ...current, open: false, resolve: null }));
    };

    const cancel = () => {
        if (processing) {
            return;
        }

        close(false);
    };

    const accept = async () => {
        const action = state.options?.action;

        if (!action) {
            close(true);
            return;
        }

        setProcessing(true);
        const succeeded = await action().then(
            () => true,
            () => false,
        );
        setProcessing(false);

        if (succeeded) {
            close(true);
        }
    };

    return (
        <ConfirmContext.Provider value={{ confirm }}>
            {children}

            <Dialog open={state.open} onClose={cancel} title={state.options?.title ?? ''}>
                <p className="mt-0 mb-0 leading-relaxed text-ink">{state.options?.description}</p>
                <div className="mt-5 flex justify-end gap-3">
                    <Button variant="outline" size="md" onClick={cancel}>
                        {state.options?.cancelLabel ?? __('Cancel')}
                    </Button>
                    <Button
                        variant={state.options?.variant ?? 'primary'}
                        size="md"
                        processing={processing}
                        onClick={accept}
                    >
                        {state.options?.confirmLabel ?? __('Confirm')}
                    </Button>
                </div>
            </Dialog>
        </ConfirmContext.Provider>
    );
}
