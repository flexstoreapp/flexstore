import { useEffect, useRef } from 'react';

import { __ } from '@/lib/i18n';

export interface PaystackHandlers {
    openCheckout: (options: { accessCode: string }) => void;
}

interface PaystackCheckoutProps {
    onReady: (handlers: PaystackHandlers) => void;
    onSuccess: (response: { reference: string }) => void;
    onDismiss: () => void;
    onError: (error: string) => void;
}

interface PaystackPopInstance {
    resumeTransaction: (
        accessCode: string,
        callbacks: {
            onSuccess: (transaction: { reference: string }) => void;
            onCancel: () => void;
            onError: (error: { message?: string }) => void;
        },
    ) => void;
}

interface PaystackPopConstructor {
    new (): PaystackPopInstance;
}

const paystackLoadPromise = new Map<string, Promise<PaystackPopConstructor | null>>();

function loadPaystackSdk(): Promise<PaystackPopConstructor | null> {
    const key = 'paystack';
    const existing = paystackLoadPromise.get(key);

    if (existing) {
        return existing;
    }

    const promise = new Promise<PaystackPopConstructor | null>((resolve) => {
        if ((window as unknown as Record<string, unknown>).PaystackPop) {
            resolve((window as unknown as Record<string, unknown>).PaystackPop as PaystackPopConstructor);

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://js.paystack.co/v2/inline.js';
        script.async = true;
        script.onload = () => {
            resolve((window as unknown as Record<string, unknown>).PaystackPop as PaystackPopConstructor);
        };
        script.onerror = () => {
            script.remove();
            resolve(null);
        };
        document.head.appendChild(script);
    });

    paystackLoadPromise.set(key, promise);

    promise.then((PaystackPop) => {
        if (!PaystackPop) {
            paystackLoadPromise.delete(key);
        }
    });

    return promise;
}

export function PaystackCheckout({ onReady, onSuccess, onDismiss, onError }: PaystackCheckoutProps) {
    const initializedRef = useRef(false);

    const callbacksRef = useRef({ onReady, onSuccess, onDismiss, onError });
    useEffect(() => {
        callbacksRef.current = { onReady, onSuccess, onDismiss, onError };
    });

    useEffect(() => {
        if (initializedRef.current) {
            return;
        }

        initializedRef.current = true;

        loadPaystackSdk().then((PaystackPop) => {
            if (!PaystackPop) {
                callbacksRef.current.onError(__('Failed to load Paystack SDK.'));

                return;
            }

            callbacksRef.current.onReady({
                openCheckout: ({ accessCode }) => {
                    new PaystackPop().resumeTransaction(accessCode, {
                        onSuccess: (transaction: { reference: string }) => {
                            callbacksRef.current.onSuccess(transaction);
                        },
                        onCancel: () => callbacksRef.current.onDismiss(),
                        onError: (error: { message?: string }) => {
                            callbacksRef.current.onError(error.message ?? __('Payment failed. Please try again.'));
                        },
                    });
                },
            });
        });
    }, []);

    return null;
}
