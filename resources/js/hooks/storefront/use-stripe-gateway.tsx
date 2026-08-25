import { router } from '@inertiajs/react';
import { lazy, useEffect, useRef, useState } from 'react';

import type {
    StripeBillingDetails,
    StripeHandlers,
} from '@/components/storefront/checkout/gateway/stripe-payment-element';
import { multiply } from '@/lib/decimal';
import { handlePaymentError } from '@/lib/handle-payment-error';
import { httpPost, isAbortError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import type { AddressFormValues, GatewayContext, PaymentGatewayAdapter, PaymentOption } from '@/types';

import { useGatewayHandlers } from './use-gateway-handlers';
import { useFormatMoney } from '../use-format-money';

function buildBillingDetails(formData: Record<string, unknown>): StripeBillingDetails | undefined {
    const shipping = formData.shipping_address as Partial<AddressFormValues> | undefined;
    const billing = formData.billing_address as Partial<AddressFormValues> | undefined;
    const address =
        formData.different_billing_address === true ? billing : shipping?.address_line_1 ? shipping : billing;

    const email = typeof formData.customer_email === 'string' ? formData.customer_email : '';
    const name = address ? `${address.first_name ?? ''} ${address.last_name ?? ''}`.trim() : '';

    const addressFields = address
        ? clean({
              line1: address.address_line_1,
              line2: address.address_line_2,
              city: address.city,
              state: address.state,
              postal_code: address.postal_code,
              country: address.country_code,
          })
        : undefined;

    const details = clean({
        name: name || undefined,
        email: email || undefined,
        phone: address?.phone || undefined,
        address: addressFields && Object.keys(addressFields).length > 0 ? addressFields : undefined,
    });

    return Object.keys(details).length > 0 ? details : undefined;
}

function clean<T extends Record<string, unknown>>(source: T): Partial<T> {
    return Object.fromEntries(
        Object.entries(source).filter(([, value]) => value !== undefined && value !== ''),
    ) as Partial<T>;
}

const StripePaymentElement = lazy(() =>
    import('@/components/storefront/checkout/gateway/stripe-payment-element').then((m) => ({
        default: m.StripePaymentElement,
    })),
);

export function useStripeGateway(ctx: GatewayContext): PaymentGatewayAdapter {
    const isEmbedded = ctx.option?.driver === 'stripe' && ctx.option?.checkout_mode === 'embedded';
    const { formatMoney } = useFormatMoney();
    const [processing, setProcessing] = useState(false);
    const { handlersRef, handleReady, ready } = useGatewayHandlers<StripeHandlers>(
        isEmbedded,
        Boolean(ctx.option?.publishable_key),
    );
    const [error, setError] = useState<string | null>(null);
    const processingRef = useRef(false);
    const abortControllerRef = useRef<AbortController | null>(null);
    const cancelUrlRef = useRef<string | null>(null);

    const ctxRef = useRef(ctx);
    useEffect(() => {
        ctxRef.current = ctx;
    });

    const [prevIsEmbedded, setPrevIsEmbedded] = useState(isEmbedded);
    if (isEmbedded !== prevIsEmbedded) {
        setPrevIsEmbedded(isEmbedded);
        if (isEmbedded) {
            setError(null);
            setProcessing(false);
        }
    }

    useEffect(() => {
        if (!isEmbedded) {
            abortControllerRef.current?.abort();
        }
        processingRef.current = false;
    }, [isEmbedded]);

    useEffect(() => {
        return () => {
            abortControllerRef.current?.abort();
        };
    }, []);

    const handleSubmit = async () => {
        if (processingRef.current) {
            return;
        }

        if (!handlersRef.current) {
            setError(__('Payment system is not ready. Please reload the page.'));

            return;
        }

        processingRef.current = true;
        const checkoutCtx = ctxRef.current;
        checkoutCtx.setSubmitting(true);
        setProcessing(true);
        setError(null);

        abortControllerRef.current?.abort();
        abortControllerRef.current = new AbortController();

        try {
            await handlersRef.current.submit();

            const response = await httpPost<{ client_secret: string; return_url: string; cancel_url: string }>(
                checkoutCtx.submitUrl,
                checkoutCtx.formData,
                { signal: abortControllerRef.current.signal },
            );

            cancelUrlRef.current = response.cancel_url ?? null;

            if (!response.client_secret || !response.return_url) {
                throw new Error(__('Invalid payment response. Please try again.'));
            }

            await handlersRef.current.confirmPayment(response.client_secret, response.return_url);

            router.visit(response.return_url);
        } catch (err) {
            if (isAbortError(err)) {
                processingRef.current = false;
                setProcessing(false);
                checkoutCtx.setSubmitting(false);
                return;
            }
            if (cancelUrlRef.current) {
                httpPost(cancelUrlRef.current).catch(() => {});
                cancelUrlRef.current = null;
            }
            handlePaymentError(err, checkoutCtx.setFormError, setError, checkoutCtx.scrollToFirstError);
            processingRef.current = false;
            setProcessing(false);
            checkoutCtx.setSubmitting(false);
        }
    };

    const renderInlineContent = (option: PaymentOption) => {
        if (option.driver !== 'stripe' || option.checkout_mode !== 'embedded' || !option.publishable_key) {
            return null;
        }

        return (
            <StripePaymentElement
                publishableKey={option.publishable_key}
                amount={Number(multiply(ctx.amount, 10 ** ctx.currencyDecimalPlaces, 0))}
                billingDetails={buildBillingDetails(ctx.formData)}
                onReady={handleReady}
            />
        );
    };

    return {
        processing: isEmbedded && processing,
        ready,
        error: isEmbedded ? error : null,
        replacesSubmitButton: false,
        submitButtonText: __('Pay :amount', { amount: formatMoney(ctx.amount, ctx.currencyCode) }),
        handleSubmit,
        renderInlineContent: isEmbedded && ctx.option?.publishable_key ? renderInlineContent : null,
        renderHiddenComponent: null,
        renderSubmitAction: null,
    };
}
