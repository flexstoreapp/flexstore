import { Elements, PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { loadStripe, type Stripe } from '@stripe/stripe-js';
import { useEffect, useRef, useState } from 'react';

import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export interface StripeBillingDetails {
    name?: string;
    email?: string;
    phone?: string;
    address?: {
        line1?: string;
        line2?: string;
        city?: string;
        state?: string;
        postal_code?: string;
        country?: string;
    };
}

interface StripePaymentFormProps {
    onReady: (handlers: StripeHandlers) => void;
    billingDetails?: StripeBillingDetails;
}

export interface StripeHandlers {
    submit: () => Promise<void>;
    confirmPayment: (clientSecret: string, returnUrl: string) => Promise<void>;
}

function StripePaymentForm({ onReady, billingDetails }: StripePaymentFormProps) {
    const stripe = useStripe();
    const elements = useElements();

    const readyFired = useRef(false);

    function handleReady() {
        if (readyFired.current) {
            return;
        }

        readyFired.current = true;
        onReady({
            async submit() {
                if (!elements) {
                    throw new Error(__('Failed to load Stripe SDK.'));
                }

                const { error } = await elements.submit();

                if (error) {
                    throw error;
                }
            },
            async confirmPayment(clientSecret: string, returnUrl: string) {
                if (!stripe || !elements) {
                    throw new Error(__('Failed to load Stripe SDK.'));
                }

                const { error } = await stripe.confirmPayment({
                    elements,
                    clientSecret,
                    confirmParams: {
                        return_url: returnUrl,
                    },
                    redirect: 'if_required',
                });

                if (error) {
                    throw error;
                }
            },
        });
    }

    return (
        <PaymentElement
            onReady={handleReady}
            options={billingDetails ? { defaultValues: { billingDetails } } : undefined}
        />
    );
}

const stripeInstances = new Map<string, Promise<Stripe | null>>();

function getStripeInstance(publishableKey: string): Promise<Stripe | null> {
    const existing = stripeInstances.get(publishableKey);

    if (existing) {
        return existing;
    }

    const instance = loadStripe(publishableKey);
    stripeInstances.set(publishableKey, instance);

    instance.then((stripe) => {
        if (!stripe) {
            stripeInstances.delete(publishableKey);
        }
    });

    return instance;
}

interface StripePaymentElementProps {
    publishableKey: string;
    amount: number;
    onReady: (handlers: StripeHandlers) => void;
    billingDetails?: StripeBillingDetails;
}

export function StripePaymentElement({ publishableKey, amount, onReady, billingDetails }: StripePaymentElementProps) {
    const [ready, setReady] = useState(false);
    const [sdkFailed, setSdkFailed] = useState(false);
    const stripePromise = getStripeInstance(publishableKey);
    const { currencyFormat } = useFormatMoney();
    const checkedRef = useRef(false);

    useEffect(() => {
        if (checkedRef.current) {
            return;
        }

        checkedRef.current = true;

        stripePromise.then((instance) => {
            if (!instance) {
                setSdkFailed(true);
            }
        });
    }, [stripePromise]);

    function handleReady(handlers: StripeHandlers) {
        setReady(true);
        onReady(handlers);
    }

    if (sdkFailed) {
        return (
            <div className="mt-2 flex items-center justify-center pb-2">
                <div className="text-sm text-error">{__('Failed to load Stripe SDK.')}</div>
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
            <div className={cn(!ready && 'h-0 overflow-hidden')}>
                <Elements
                    stripe={stripePromise}
                    options={{
                        mode: 'payment',
                        amount,
                        currency: currencyFormat.code,
                    }}
                >
                    <StripePaymentForm onReady={handleReady} billingDetails={billingDetails} />
                </Elements>
            </div>
        </>
    );
}
