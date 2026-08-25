import { createContext, useContext, useState } from 'react';

import { SubmitButton } from '@/components/admin/submit-button';
import {
    AdaptiveDialog,
    AdaptiveDialogClose,
    AdaptiveDialogContent,
    AdaptiveDialogDescription,
    AdaptiveDialogFooter,
    AdaptiveDialogHeader,
    AdaptiveDialogTitle,
} from '@/components/ui/adaptive-dialog';
import { Button } from '@/components/ui/button';
import { __ } from '@/lib/i18n';

interface ConfirmOptions {
    variant?: 'default' | 'delete';
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    action?: () => Promise<unknown>;
}

interface ConfirmState {
    isOpen: boolean;
    options: ConfirmOptions | null;
    resolve: ((value: boolean) => void) | null;
}

interface ConfirmContext {
    confirm: (options: ConfirmOptions) => Promise<boolean>;
}

const ConfirmContext = createContext<ConfirmContext | null>(null);

export function useConfirm() {
    const context = useContext(ConfirmContext);

    if (!context) {
        throw new Error('useConfirm must be used within a ConfirmProvider');
    }

    return context;
}

export function ConfirmProvider({ children }: React.PropsWithChildren) {
    const [state, setState] = useState<ConfirmState>({
        isOpen: false,
        options: null,
        resolve: null,
    });
    const [processing, setProcessing] = useState(false);

    const confirm = (options: ConfirmOptions): Promise<boolean> => {
        return new Promise<boolean>((resolve) => {
            setState({ isOpen: true, options, resolve });
        });
    };

    const handleClose = () => {
        if (processing) return;

        if (state.resolve) state.resolve(false);

        setState({ isOpen: false, options: null, resolve: null });
    };

    const handleConfirm = async () => {
        if (state.options?.action) {
            setProcessing(true);

            try {
                await state.options.action();
            } finally {
                setProcessing(false);
                setState({ isOpen: false, options: null, resolve: null });
            }

            return;
        }

        if (state.resolve) state.resolve(true);

        setState({ isOpen: false, options: null, resolve: null });
    };

    return (
        <ConfirmContext.Provider value={{ confirm }}>
            {children}

            <AdaptiveDialog open={state.isOpen} onOpenChange={(open) => !open && handleClose()}>
                <AdaptiveDialogContent>
                    <AdaptiveDialogHeader>
                        <AdaptiveDialogTitle>{state.options?.title}</AdaptiveDialogTitle>
                        <AdaptiveDialogDescription>{state.options?.description}</AdaptiveDialogDescription>
                    </AdaptiveDialogHeader>
                    <AdaptiveDialogFooter>
                        <AdaptiveDialogClose className="order-1 md:order-0" disabled={processing} asChild>
                            <Button variant="ghost">
                                {state.options?.cancelLabel ??
                                    (state.options?.variant === 'delete' ? __('No, keep it') : __('Cancel'))}
                            </Button>
                        </AdaptiveDialogClose>
                        <SubmitButton
                            variant={state.options?.variant === 'delete' ? 'destructive' : 'default'}
                            onClick={handleConfirm}
                            processing={processing}
                            className="order-0 md:order-1"
                        >
                            {state.options?.confirmLabel ??
                                (state.options?.variant === 'delete' ? __('Yes, delete') : __('Confirm'))}
                        </SubmitButton>
                    </AdaptiveDialogFooter>
                </AdaptiveDialogContent>
            </AdaptiveDialog>
        </ConfirmContext.Provider>
    );
}
