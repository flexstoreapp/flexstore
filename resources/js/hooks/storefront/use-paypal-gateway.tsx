import { router } from '@inertiajs/react';
import { lazy, useEffect, useRef, useState } from 'react';

import { handlePaymentError } from '@/lib/handle-payment-error';
import { httpPost, isAbortError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import type { GatewayContext, PaymentGatewayAdapter } from '@/types';

import { useFormatMoney } from '../use-format-money';

const PaypalButtonElement = lazy(() =>
    import('@/components/storefront/checkout/gateway/paypal-button-element').then((m) => ({
        default: m.PaypalButtonElement,
    })),
);

export function usePaypalGateway(ctx: GatewayContext): PaymentGatewayAdapter {
    const isEmbedded = ctx.option?.driver === 'paypal' && ctx.option?.checkout_mode === 'embedded';
    const { formatMoney } = useFormatMoney();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const returnUrlRef = useRef<string | null>(null);
    const cancelUrlRef = useRef<string | null>(null);
    const errorHandledRef = useRef(false);
    const abortControllerRef = useRef<AbortController | null>(null);

    const ctxRef = useRef(ctx);
    useEffect(() => {
        ctxRef.current = ctx;
    });

    const processingRef = useRef(false);

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

    const handleCreateOrder = async (): Promise<string> => {
        if (processingRef.current) {
            return Promise.reject(new Error(__('Payment already in progress.')));
        }

        processingRef.current = true;
        returnUrlRef.current = null;
        const checkoutCtx = ctxRef.current;
        errorHandledRef.current = false;
        checkoutCtx.setSubmitting(true);
        setProcessing(true);
        setError(null);

        abortControllerRef.current?.abort();
        abortControllerRef.current = new AbortController();

        try {
            const response = await httpPost<{ paypal_order_id: string; return_url: string; cancel_url: string }>(
                checkoutCtx.submitUrl,
                checkoutCtx.formData,
                { signal: abortControllerRef.current.signal },
            );

            returnUrlRef.current = response.return_url;
            cancelUrlRef.current = response.cancel_url ?? null;

            return response.paypal_order_id;
        } catch (err) {
            processingRef.current = false;
            setProcessing(false);
            checkoutCtx.setSubmitting(false);

            if (isAbortError(err)) {
                throw err;
            }

            errorHandledRef.current = true;
            handlePaymentError(err, checkoutCtx.setFormError, setError, checkoutCtx.scrollToFirstError);
            throw err;
        }
    };

    const handleApprove = () => {
        if (returnUrlRef.current) {
            router.visit(returnUrlRef.current);
        } else {
            setError(__('Payment was approved but redirect failed. Please contact support.'));
            processingRef.current = false;
            setProcessing(false);
            ctxRef.current.setSubmitting(false);
        }
    };

    const handleCancel = () => {
        if (cancelUrlRef.current) {
            httpPost(cancelUrlRef.current).catch(() => {});
            cancelUrlRef.current = null;
        }
        errorHandledRef.current = false;
        processingRef.current = false;
        setProcessing(false);
        ctxRef.current.setSubmitting(false);
    };

    const handleError = (errorMessage: string) => {
        if (cancelUrlRef.current) {
            httpPost(cancelUrlRef.current).catch(() => {});
            cancelUrlRef.current = null;
        }
        if (!errorHandledRef.current) {
            setError(errorMessage);
        }
        errorHandledRef.current = false;
        processingRef.current = false;
        setProcessing(false);
        ctxRef.current.setSubmitting(false);
    };

    const renderSubmitAction = () => (
        <PaypalButtonElement
            clientId={ctx.option!.client_id!}
            onCreateOrder={handleCreateOrder}
            onApprove={handleApprove}
            onCancel={handleCancel}
            onError={handleError}
        />
    );

    return {
        processing: isEmbedded && processing,
        ready: true,
        error: isEmbedded ? error : null,
        replacesSubmitButton: isEmbedded,
        submitButtonText: __('Pay :amount', { amount: formatMoney(ctx.amount, ctx.currencyCode) }),
        handleSubmit: async () => {},
        renderInlineContent: null,
        renderHiddenComponent: null,
        renderSubmitAction: isEmbedded && ctx.option?.client_id ? renderSubmitAction : null,
    };
}
