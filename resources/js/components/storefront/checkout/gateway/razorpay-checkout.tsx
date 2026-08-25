import { useEffect, useRef } from 'react';

import { __ } from '@/lib/i18n';

export interface RazorpayHandlers {
    openCheckout: (options: {
        orderId: string;
        keyId: string;
        amount: number;
        currency: string;
        name: string;
        prefill?: { name?: string; email?: string; contact?: string };
    }) => void;
}

interface RazorpayCheckoutProps {
    onReady: (handlers: RazorpayHandlers) => void;
    onSuccess: (response: {
        razorpay_payment_id: string;
        razorpay_order_id: string;
        razorpay_signature: string;
    }) => void;
    onDismiss: () => void;
    onError: (error: string) => void;
}

interface RazorpayInstance {
    open: () => void;
}

interface RazorpayConstructor {
    new (options: Record<string, unknown>): RazorpayInstance;
}

const razorpayLoadPromise = new Map<string, Promise<RazorpayConstructor | null>>();

function loadRazorpaySdk(): Promise<RazorpayConstructor | null> {
    const key = 'razorpay';
    const existing = razorpayLoadPromise.get(key);

    if (existing) {
        return existing;
    }

    const promise = new Promise<RazorpayConstructor | null>((resolve) => {
        if ((window as unknown as Record<string, unknown>).Razorpay) {
            resolve((window as unknown as Record<string, unknown>).Razorpay as RazorpayConstructor);

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.async = true;
        script.onload = () => {
            resolve((window as unknown as Record<string, unknown>).Razorpay as RazorpayConstructor);
        };
        script.onerror = () => {
            script.remove();
            resolve(null);
        };
        document.head.appendChild(script);
    });

    razorpayLoadPromise.set(key, promise);

    promise.then((Razorpay) => {
        if (!Razorpay) {
            razorpayLoadPromise.delete(key);
        }
    });

    return promise;
}

export function RazorpayCheckout({ onReady, onSuccess, onDismiss, onError }: RazorpayCheckoutProps) {
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

        loadRazorpaySdk().then((Razorpay) => {
            if (!Razorpay) {
                callbacksRef.current.onError(__('Failed to load Razorpay SDK.'));

                return;
            }

            callbacksRef.current.onReady({
                openCheckout: ({ orderId, keyId: key, amount, currency, name, prefill }) => {
                    const rzp = new Razorpay({
                        key: key,
                        amount,
                        currency,
                        name,
                        order_id: orderId,
                        handler: (response: {
                            razorpay_payment_id: string;
                            razorpay_order_id: string;
                            razorpay_signature: string;
                        }) => {
                            callbacksRef.current.onSuccess(response);
                        },
                        prefill: prefill ?? {},
                        modal: {
                            ondismiss: () => callbacksRef.current.onDismiss(),
                        },
                    });

                    rzp.open();
                },
            });
        });
    }, []);

    return null;
}
