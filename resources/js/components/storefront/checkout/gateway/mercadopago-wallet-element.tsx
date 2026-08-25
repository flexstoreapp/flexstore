import { useEffect, useRef, useState } from 'react';

import { __ } from '@/lib/i18n';

interface MercadoPagoBrickController {
    unmount: () => void;
}

interface MercadoPagoBricksBuilder {
    create: (
        brick: string,
        container: string,
        settings: {
            initialization: { redirectMode?: 'self' | 'blank' | 'modal' };
            callbacks: {
                onReady?: () => void;
                onSubmit: () => Promise<string>;
                onError?: (error: { message?: string }) => void;
            };
        },
    ) => Promise<MercadoPagoBrickController>;
}

interface MercadoPagoInstance {
    bricks: () => MercadoPagoBricksBuilder;
}

interface MercadoPagoConstructor {
    new (publicKey: string, options?: { locale?: string }): MercadoPagoInstance;
}

const mercadoPagoLoadPromise = new Map<string, Promise<MercadoPagoConstructor | null>>();

function loadMercadoPagoSdk(): Promise<MercadoPagoConstructor | null> {
    const key = 'mercadopago';
    const existing = mercadoPagoLoadPromise.get(key);

    if (existing) {
        return existing;
    }

    const promise = new Promise<MercadoPagoConstructor | null>((resolve) => {
        if ((window as unknown as Record<string, unknown>).MercadoPago) {
            resolve((window as unknown as Record<string, unknown>).MercadoPago as MercadoPagoConstructor);

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://sdk.mercadopago.com/js/v2';
        script.async = true;
        script.onload = () => {
            resolve((window as unknown as Record<string, unknown>).MercadoPago as MercadoPagoConstructor);
        };
        script.onerror = () => {
            script.remove();
            resolve(null);
        };
        document.head.appendChild(script);
    });

    mercadoPagoLoadPromise.set(key, promise);

    promise.then((MercadoPago) => {
        if (!MercadoPago) {
            mercadoPagoLoadPromise.delete(key);
        }
    });

    return promise;
}

interface MercadoPagoWalletElementProps {
    publicKey: string;
    locale: string;
    onCreatePreference: () => Promise<string>;
    onError: (error: string) => void;
}

export function MercadoPagoWalletElement({
    publicKey,
    locale,
    onCreatePreference,
    onError,
}: MercadoPagoWalletElementProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const controllerRef = useRef<MercadoPagoBrickController | null>(null);
    const initializedRef = useRef(false);
    const [loading, setLoading] = useState(true);

    const callbacksRef = useRef({ onCreatePreference, onError });
    useEffect(() => {
        callbacksRef.current = { onCreatePreference, onError };
    });

    useEffect(() => {
        if (initializedRef.current) {
            return;
        }

        initializedRef.current = true;

        loadMercadoPagoSdk().then((MercadoPago) => {
            if (!MercadoPago || !containerRef.current) {
                setLoading(false);
                callbacksRef.current.onError(__('Failed to load Mercado Pago SDK.'));

                return;
            }

            const mp = new MercadoPago(publicKey, { locale });

            mp.bricks()
                .create('wallet', 'mercadopago-wallet-container', {
                    initialization: { redirectMode: 'modal' },
                    callbacks: {
                        onReady: () => setLoading(false),
                        onSubmit: () => callbacksRef.current.onCreatePreference(),
                        onError: (error) =>
                            callbacksRef.current.onError(error.message ?? __('An error occurred with Mercado Pago.')),
                    },
                })
                .then((controller) => {
                    controllerRef.current = controller;
                })
                .catch(() => {
                    setLoading(false);
                    callbacksRef.current.onError(__('Failed to load Mercado Pago SDK.'));
                });
        });

        return () => {
            try {
                controllerRef.current?.unmount();
            } catch {
                // ignore unmount errors
            }
            controllerRef.current = null;
        };
    }, [publicKey, locale]);

    return (
        <>
            {loading && (
                <div className="mt-2 flex items-center justify-center pb-2">
                    <div className="text-sm text-muted">{__('Loading...')}</div>
                </div>
            )}
            <div id="mercadopago-wallet-container" ref={containerRef} />
        </>
    );
}
