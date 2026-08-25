import { useEffect, useRef, useState } from 'react';

import { __ } from '@/lib/i18n';

declare global {
    interface Window {
        Mollie?: (profileId: string, options?: { testmode?: boolean; locale?: string }) => MollieInstance;
    }
}

interface MollieInstance {
    createComponent: (type: string) => MollieComponent;
    createToken: () => Promise<{ token: string; error?: { message: string } }>;
}

interface MollieChangeEvent {
    error?: string;
    touched: boolean;
}

interface MollieComponent {
    mount: (element: string | HTMLElement) => void;
    unmount: () => void;
    addEventListener: (event: string, callback: (event: MollieChangeEvent) => void) => void;
}

export interface MollieCardHandlers {
    createToken: () => Promise<string>;
}

interface MollieCardElementProps {
    profileId: string;
    testmode: boolean;
    onReady: (handlers: MollieCardHandlers) => void;
}

let mollieScriptLoaded = false;
let mollieScriptPromise: Promise<void> | null = null;

function loadMollieScript(): Promise<void> {
    if (mollieScriptLoaded) {
        return Promise.resolve();
    }

    if (mollieScriptPromise) {
        return mollieScriptPromise;
    }

    mollieScriptPromise = new Promise<void>((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://js.mollie.com/v1/mollie.js';
        script.async = true;
        script.onload = () => {
            mollieScriptLoaded = true;
            resolve();
        };
        script.onerror = () => {
            mollieScriptPromise = null;
            reject(new Error(__('Failed to load Mollie SDK.')));
        };
        document.head.appendChild(script);
    });

    return mollieScriptPromise;
}

export function MollieCardElement({ profileId, testmode, onReady }: MollieCardElementProps) {
    const [ready, setReady] = useState(false);
    const [sdkFailed, setSdkFailed] = useState(false);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const componentsRef = useRef<MollieComponent[]>([]);
    const initializedRef = useRef(false);
    const onReadyRef = useRef(onReady);

    useEffect(() => {
        onReadyRef.current = onReady;
    });

    useEffect(() => {
        if (initializedRef.current) {
            return;
        }

        initializedRef.current = true;

        loadMollieScript()
            .then(() => {
                if (!window.Mollie) {
                    setSdkFailed(true);
                    return;
                }

                const mollie = window.Mollie(profileId, { testmode });

                const cardNumber = mollie.createComponent('cardNumber');
                const cardHolder = mollie.createComponent('cardHolder');
                const expiryDate = mollie.createComponent('expiryDate');
                const verificationCode = mollie.createComponent('verificationCode');

                componentsRef.current = [cardNumber, cardHolder, expiryDate, verificationCode];

                cardNumber.mount('#mollie-card-number');
                cardHolder.mount('#mollie-card-holder');
                expiryDate.mount('#mollie-expiry-date');
                verificationCode.mount('#mollie-verification-code');

                const fields = [
                    ['cardNumber', cardNumber],
                    ['cardHolder', cardHolder],
                    ['expiryDate', expiryDate],
                    ['verificationCode', verificationCode],
                ] as const;

                for (const [key, component] of fields) {
                    component.addEventListener('change', (event) => {
                        setFieldErrors((prev) => {
                            const next = { ...prev };
                            if (event.error && event.touched) {
                                next[key] = event.error;
                            } else {
                                delete next[key];
                            }
                            return next;
                        });
                    });
                }

                setReady(true);
                onReadyRef.current({
                    async createToken() {
                        const { token, error } = await mollie.createToken();

                        if (error) {
                            throw new Error(error.message);
                        }

                        return token;
                    },
                });
            })
            .catch(() => {
                setSdkFailed(true);
            });

        return () => {
            componentsRef.current.forEach((component) => {
                try {
                    component.unmount();
                } catch {
                    // ignore unmount errors
                }
            });
            componentsRef.current = [];
        };
    }, [profileId, testmode]);

    if (sdkFailed) {
        return (
            <div className="mt-2 flex items-center justify-center pb-2">
                <div className="text-sm text-error">{__('Failed to load Mollie SDK.')}</div>
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

            <div className={ready ? 'space-y-6' : 'hidden'}>
                <div>
                    <label className="mb-2 block text-sm font-semibold text-ink">{__('Card number')}</label>
                    <div
                        id="mollie-card-number"
                        className="rounded-md border border-line-strong bg-surface px-4 py-3"
                    />
                    {fieldErrors.cardNumber && (
                        <p className="mt-1.5 mb-0 text-sm text-error">{fieldErrors.cardNumber}</p>
                    )}
                </div>
                <div>
                    <label className="mb-2 block text-sm font-semibold text-ink">{__('Card holder')}</label>
                    <div
                        id="mollie-card-holder"
                        className="rounded-md border border-line-strong bg-surface px-4 py-3"
                    />
                    {fieldErrors.cardHolder && (
                        <p className="mt-1.5 mb-0 text-sm text-error">{fieldErrors.cardHolder}</p>
                    )}
                </div>
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <label className="mb-2 block text-sm font-semibold text-ink">{__('Expiry date')}</label>
                        <div
                            id="mollie-expiry-date"
                            className="rounded-md border border-line-strong bg-surface px-4 py-3"
                        />
                        {fieldErrors.expiryDate && (
                            <p className="mt-1.5 mb-0 text-sm text-error">{fieldErrors.expiryDate}</p>
                        )}
                    </div>
                    <div>
                        <label className="mb-2 block text-sm font-semibold text-ink">{__('CVV')}</label>
                        <div
                            id="mollie-verification-code"
                            className="rounded-md border border-line-strong bg-surface px-4 py-3"
                        />
                        {fieldErrors.verificationCode && (
                            <p className="mt-1.5 mb-0 text-sm text-error">{fieldErrors.verificationCode}</p>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
