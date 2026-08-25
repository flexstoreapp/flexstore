import { router } from '@inertiajs/react';
import { lazy, useEffect, useRef, useState } from 'react';

import type { RazorpayHandlers } from '@/components/storefront/checkout/gateway/razorpay-checkout';
import { multiply } from '@/lib/decimal';
import { handlePaymentError } from '@/lib/handle-payment-error';
import { httpPost, isAbortError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import type { GatewayContext, PaymentGatewayAdapter } from '@/types';

import { useGatewayHandlers } from './use-gateway-handlers';
import { useFormatMoney } from '../use-format-money';

const RazorpayCheckout = lazy(() =>
    import('@/components/storefront/checkout/gateway/razorpay-checkout').then((m) => ({ default: m.RazorpayCheckout })),
);

interface CheckoutFormData {
    different_billing_address?: boolean;
    billing_address?: { first_name?: string; last_name?: string; phone?: string };
    shipping_address?: { first_name?: string; last_name?: string; phone?: string };
    customer_email?: string;
}

function billingAddress(formData: CheckoutFormData): CheckoutFormData['billing_address'] {
    return formData.different_billing_address ? formData.billing_address : formData.shipping_address;
}

function getPrefillName(formData: CheckoutFormData): string | undefined {
    const addr = billingAddress(formData);
    return `${addr?.first_name ?? ''} ${addr?.last_name ?? ''}`.trim() || undefined;
}

function getPrefillEmail(formData: CheckoutFormData): string | undefined {
    return formData.customer_email || undefined;
}

function getPrefillContact(formData: CheckoutFormData): string | undefined {
    return billingAddress(formData)?.phone || undefined;
}

export function useRazorpayGateway(ctx: GatewayContext): PaymentGatewayAdapter {
    const isEmbedded = ctx.option?.driver === 'razorpay' && ctx.option?.checkout_mode === 'embedded';
    const { formatMoney } = useFormatMoney();
    const [processing, setProcessing] = useState(false);
    const { handlersRef, handleReady, ready } = useGatewayHandlers<RazorpayHandlers>(
        isEmbedded,
        Boolean(ctx.option?.key_id),
    );
    const [error, setError] = useState<string | null>(null);
    const returnUrlRef = useRef<string | null>(null);
    const cancelUrlRef = useRef<string | null>(null);
    const processingRef = useRef(false);
    const abortControllerRef = useRef<AbortController | null>(null);

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
        returnUrlRef.current = null;
        const checkoutCtx = ctxRef.current;
        checkoutCtx.setSubmitting(true);
        setProcessing(true);
        setError(null);

        abortControllerRef.current?.abort();
        abortControllerRef.current = new AbortController();

        try {
            const response = await httpPost<{
                razorpay_order_id: string;
                key_id: string;
                return_url: string;
                cancel_url: string;
            }>(checkoutCtx.submitUrl, checkoutCtx.formData, {
                signal: abortControllerRef.current.signal,
            });

            returnUrlRef.current = response.return_url;
            cancelUrlRef.current = response.cancel_url ?? null;

            handlersRef.current.openCheckout({
                orderId: response.razorpay_order_id,
                keyId: response.key_id,
                amount: Number(multiply(checkoutCtx.amount, 10 ** checkoutCtx.currencyDecimalPlaces, 0)),
                currency: checkoutCtx.currencyCode.toUpperCase(),
                name: checkoutCtx.storeName,
                prefill: {
                    name: getPrefillName(checkoutCtx.formData),
                    email: getPrefillEmail(checkoutCtx.formData),
                    contact: getPrefillContact(checkoutCtx.formData),
                },
            });
        } catch (err) {
            if (isAbortError(err)) {
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

    const handleSuccess = () => {
        if (returnUrlRef.current) {
            router.visit(returnUrlRef.current);
        } else {
            setError(__('Payment was approved but redirect failed. Please contact support.'));
            processingRef.current = false;
            setProcessing(false);
            ctxRef.current.setSubmitting(false);
        }
    };

    const handleDismiss = () => {
        if (cancelUrlRef.current) {
            httpPost(cancelUrlRef.current).catch(() => {});
            cancelUrlRef.current = null;
        }
        setError(null);
        processingRef.current = false;
        setProcessing(false);
        ctxRef.current.setSubmitting(false);
    };

    const handleError = (errorMessage: string) => {
        if (cancelUrlRef.current) {
            httpPost(cancelUrlRef.current).catch(() => {});
            cancelUrlRef.current = null;
        }
        setError(errorMessage);
        processingRef.current = false;
        setProcessing(false);
        ctxRef.current.setSubmitting(false);
    };

    const renderHiddenComponent = () => (
        <RazorpayCheckout
            onReady={handleReady}
            onSuccess={handleSuccess}
            onDismiss={handleDismiss}
            onError={handleError}
        />
    );

    return {
        processing: isEmbedded && processing,
        ready,
        error: isEmbedded ? error : null,
        replacesSubmitButton: false,
        submitButtonText: __('Pay :amount', { amount: formatMoney(ctx.amount, ctx.currencyCode) }),
        handleSubmit,
        renderInlineContent: null,
        renderHiddenComponent: isEmbedded && ctx.option?.key_id ? renderHiddenComponent : null,
        renderSubmitAction: null,
    };
}
