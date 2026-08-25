import { useEffect, useRef, useState } from 'react';

import { __ } from '@/lib/i18n';

interface TapCardConfig {
    publicKey: string;
    transaction: { amount: number; currency: string };
    customer?: Record<string, unknown>;
    interface?: { locale?: string; theme?: string };
    fields?: { cardHolder?: boolean };
    addons?: { loader?: boolean; displayPaymentBrands?: boolean };
    onReady?: () => void;
    onSuccess?: (data: { id: string }) => void;
    onError?: (error: { message?: string } | unknown) => void;
}

interface TapCardSdk {
    renderTapCard: (elementId: string, config: TapCardConfig) => { unmount: () => void };
    tokenize: () => void;
}

declare global {
    interface Window {
        CardSDK?: TapCardSdk;
    }
}

export interface TapCardHandlers {
    createToken: () => Promise<string>;
}

interface TapCardElementProps {
    publicKey: string;
    amount: number;
    currencyCode: string;
    onReady: (handlers: TapCardHandlers) => void;
}

const INITIALIZATION_TIMEOUT_MS = 15000;

let tapScriptPromise: Promise<TapCardSdk | null> | null = null;

function loadTapSdk(): Promise<TapCardSdk | null> {
    if (window.CardSDK) {
        return Promise.resolve(window.CardSDK);
    }

    if (tapScriptPromise) {
        return tapScriptPromise;
    }

    tapScriptPromise = new Promise<TapCardSdk | null>((resolve) => {
        const script = document.createElement('script');
        script.src = 'https://tap-sdks.b-cdn.net/card/1.0.2/index.js';
        script.async = true;
        script.onload = () => resolve(window.CardSDK ?? null);
        script.onerror = () => {
            script.remove();
            tapScriptPromise = null;
            resolve(null);
        };
        document.head.appendChild(script);
    });

    return tapScriptPromise;
}

function errorMessage(error: unknown): string {
    if (typeof error === 'object' && error !== null && 'message' in error) {
        return String((error as { message?: string }).message ?? '');
    }

    return '';
}

export function TapCardElement({ publicKey, amount, currencyCode, onReady }: TapCardElementProps) {
    const [ready, setReady] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const initializedRef = useRef(false);
    const settledRef = useRef(false);
    const controllerRef = useRef<{ unmount: () => void } | null>(null);
    const tokenResolverRef = useRef<{ resolve: (token: string) => void; reject: (error: Error) => void } | null>(null);

    const onReadyRef = useRef(onReady);
    const amountRef = useRef(amount);
    const currencyRef = useRef(currencyCode);
    useEffect(() => {
        onReadyRef.current = onReady;
        amountRef.current = amount;
        currencyRef.current = currencyCode;
    });

    useEffect(() => {
        if (initializedRef.current) {
            return;
        }

        initializedRef.current = true;

        let timeout: ReturnType<typeof setTimeout> | undefined;

        const failInitialization = () => {
            settledRef.current = true;
            setError(__('Could not load the Tap payment form. Please refresh and try again.'));
        };

        const createToken = () =>
            new Promise<string>((resolve, reject) => {
                tokenResolverRef.current = { resolve, reject };

                if (!window.CardSDK) {
                    reject(new Error(__('Payment system is not ready. Please reload the page.')));
                    tokenResolverRef.current = null;

                    return;
                }

                window.CardSDK.tokenize();
            });

        loadTapSdk().then((sdk) => {
            if (!sdk) {
                settledRef.current = true;
                setError(__('Failed to load Tap SDK.'));

                return;
            }

            try {
                controllerRef.current = sdk.renderTapCard('tap-card-element', {
                    publicKey,
                    transaction: { amount: amountRef.current, currency: currencyRef.current },
                    interface: { locale: 'en', theme: 'light' },
                    fields: { cardHolder: true },
                    addons: { loader: true, displayPaymentBrands: true },
                    onReady: () => {
                        settledRef.current = true;
                        setError(null);
                        setReady(true);
                        onReadyRef.current({ createToken });
                    },
                    onSuccess: (data) => {
                        tokenResolverRef.current?.resolve(data.id);
                        tokenResolverRef.current = null;
                    },
                    onError: (err) => {
                        if (tokenResolverRef.current) {
                            tokenResolverRef.current.reject(
                                new Error(errorMessage(err) || __('Card verification failed.')),
                            );
                            tokenResolverRef.current = null;

                            return;
                        }

                        failInitialization();
                    },
                });

                timeout = setTimeout(() => {
                    if (!settledRef.current) {
                        failInitialization();
                    }
                }, INITIALIZATION_TIMEOUT_MS);
            } catch {
                failInitialization();
            }
        });

        return () => {
            if (timeout) {
                clearTimeout(timeout);
            }
            try {
                controllerRef.current?.unmount();
            } catch {
                // ignore unmount errors
            }
            controllerRef.current = null;
        };
    }, [publicKey]);

    if (error) {
        return (
            <div className="mt-2 flex items-center justify-center pb-2">
                <div className="text-sm text-error">{error}</div>
            </div>
        );
    }

    return (
        <>
            {!ready && (
                <div className="mt-2 flex items-center justify-center pb-2">
                    <div className="text-sm text-muted">{__('Loading...')}</div>
                </div>
            )}
            <div id="tap-card-element" className={ready ? '' : 'hidden'} />
        </>
    );
}
