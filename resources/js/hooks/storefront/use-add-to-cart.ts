import type { CancelToken } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import * as CartItemController from '@/actions/App/Http/Controllers/Storefront/CartItemController';
import { useAbortableRequest } from '@/hooks/storefront/use-abortable-request';
import { __ } from '@/lib/i18n';

export type AddToCartStatus = 'idle' | 'loading' | 'success' | 'error';

interface AddToCartInput {
    productId: number;
    variantId?: string | null;
    quantity?: number;
}

interface AddToCartOptions {
    resetSuccessAfter?: number;
    resetErrorAfter?: number;
    onSuccess?: () => void;
    onError?: () => void;
}

interface Callbacks {
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;
}

export function useAddToCart() {
    const { run, abort } = useAbortableRequest();
    const [status, setStatus] = useState<AddToCartStatus>('idle');
    const [buyingNow, setBuyingNow] = useState(false);
    const [error, setError] = useState<string>();
    const resetTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const clearResetTimer = () => {
        if (resetTimer.current) {
            clearTimeout(resetTimer.current);
            resetTimer.current = null;
        }
    };

    useEffect(() => clearResetTimer, []);

    const scheduleReset = (delayMs?: number) => {
        if (delayMs) {
            clearResetTimer();
            resetTimer.current = setTimeout(() => setStatus('idle'), delayMs);
        }
    };

    const failed = (errors: Record<string, string>) => {
        setStatus('error');
        setError(Object.values(errors)[0] ?? __('Could not add to cart.'));
    };

    const post = (
        input: AddToCartInput,
        buyNow: boolean,
        capture: (token: CancelToken) => void,
        callbacks: Callbacks,
    ) =>
        router.post(
            CartItemController.store.url(),
            {
                product_id: input.productId,
                product_variant_id: input.variantId ?? null,
                quantity: input.quantity ?? 1,
                buy_now: buyNow,
            },
            { preserveScroll: true, preserveState: true, onCancelToken: capture, ...callbacks },
        );

    const addToCart = (input: AddToCartInput, options?: AddToCartOptions) => {
        clearResetTimer();
        setStatus('loading');
        run((capture) =>
            post(input, false, capture, {
                onSuccess: () => {
                    setStatus('success');
                    options?.onSuccess?.();
                    scheduleReset(options?.resetSuccessAfter);
                },
                onError: (errors) => {
                    failed(errors);
                    options?.onError?.();
                    scheduleReset(options?.resetErrorAfter);
                },
            }),
        );
    };

    const buyNow = (input: AddToCartInput) => {
        clearResetTimer();
        setBuyingNow(true);
        run((capture) => post(input, true, capture, { onError: failed, onFinish: () => setBuyingNow(false) }));
    };

    const reset = () => {
        abort();
        clearResetTimer();
        setStatus('idle');
    };

    return { status, error, buyingNow, addToCart, buyNow, reset };
}
