import { useEffect, useRef, useState } from 'react';

import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';

interface PaypalNamespace {
    Buttons: (config: {
        createOrder: () => Promise<string>;
        onApprove: (data: { orderID: string }) => void;
        onCancel: () => void;
        onError: (err: { message?: string }) => void;
    }) => { render: (selector: string | HTMLElement) => Promise<void> };
}

const paypalInstances = new Map<string, Promise<PaypalNamespace | null>>();

function loadPaypalSdk(clientId: string, currency: string): Promise<PaypalNamespace | null> {
    const cacheKey = `${clientId}:${currency}`;
    const existing = paypalInstances.get(cacheKey);

    if (existing) {
        return existing;
    }

    const promise = new Promise<PaypalNamespace | null>((resolve) => {
        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=${encodeURIComponent(currency)}`;
        script.async = true;
        script.onload = () => {
            resolve((window as unknown as Record<string, unknown>).paypal as PaypalNamespace);
        };
        script.onerror = () => {
            script.remove();
            resolve(null);
        };
        document.head.appendChild(script);
    });

    paypalInstances.set(cacheKey, promise);

    promise.then((paypal) => {
        if (!paypal) {
            paypalInstances.delete(cacheKey);
        }
    });

    return promise;
}

interface PaypalButtonElementProps {
    clientId: string;
    onCreateOrder: () => Promise<string>;
    onApprove: () => void;
    onCancel: () => void;
    onError: (error: string) => void;
}

export function PaypalButtonElement({
    clientId,
    onCreateOrder,
    onApprove,
    onCancel,
    onError,
}: PaypalButtonElementProps) {
    const { currencyFormat } = useFormatMoney();
    const currency = currencyFormat.code.toUpperCase();
    const containerRef = useRef<HTMLDivElement>(null);
    const [loading, setLoading] = useState(true);
    const renderedRef = useRef(false);

    const callbacksRef = useRef({ onCreateOrder, onApprove, onCancel, onError });
    useEffect(() => {
        callbacksRef.current = { onCreateOrder, onApprove, onCancel, onError };
    });

    useEffect(() => {
        if (renderedRef.current) {
            return;
        }

        renderedRef.current = true;

        loadPaypalSdk(clientId, currency).then((paypal) => {
            if (!paypal || !containerRef.current) {
                setLoading(false);
                callbacksRef.current.onError(__('Failed to load PayPal SDK.'));

                return;
            }

            setLoading(false);

            paypal
                .Buttons({
                    createOrder: () => callbacksRef.current.onCreateOrder(),
                    onApprove: () => callbacksRef.current.onApprove(),
                    onCancel: () => callbacksRef.current.onCancel(),
                    onError: (err) => callbacksRef.current.onError(err.message ?? __('An error occurred with PayPal.')),
                })
                .render(containerRef.current);
        });
    }, [clientId, currency]);

    return (
        <>
            {loading && (
                <div className="mt-2 flex items-center justify-center pb-2">
                    <div className="text-sm text-muted">{__('Loading...')}</div>
                </div>
            )}
            <div ref={containerRef} />
        </>
    );
}
