import { lazy, useEffect, useRef, useState } from 'react';

import { handlePaymentError } from '@/lib/handle-payment-error';
import { httpPost, isAbortError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import type { GatewayContext, PaymentGatewayAdapter } from '@/types';

import { useFormatMoney } from '../use-format-money';

const MercadoPagoWalletElement = lazy(() =>
    import('@/components/storefront/checkout/gateway/mercadopago-wallet-element').then((m) => ({
        default: m.MercadoPagoWalletElement,
    })),
);

export function useMercadoPagoGateway(ctx: GatewayContext): PaymentGatewayAdapter {
    const isEmbedded = ctx.option?.driver === 'mercadopago' && ctx.option?.checkout_mode === 'embedded';
    const { formatMoney } = useFormatMoney();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const cancelUrlRef = useRef<string | null>(null);
    const errorHandledRef = useRef(false);
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

    const handleCreatePreference = async (): Promise<string> => {
        if (processingRef.current) {
            return Promise.reject(new Error(__('Payment already in progress.')));
        }

        processingRef.current = true;
        const checkoutCtx = ctxRef.current;
        errorHandledRef.current = false;
        checkoutCtx.setSubmitting(true);
        setProcessing(true);
        setError(null);

        abortControllerRef.current?.abort();
        abortControllerRef.current = new AbortController();

        try {
            const response = await httpPost<{ preference_id: string; return_url: string; cancel_url: string }>(
                checkoutCtx.submitUrl,
                checkoutCtx.formData,
                { signal: abortControllerRef.current.signal },
            );

            cancelUrlRef.current = response.cancel_url ?? null;

            return response.preference_id;
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
        <MercadoPagoWalletElement
            publicKey={ctx.option!.public_key!}
            locale="en-US"
            onCreatePreference={handleCreatePreference}
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
        renderSubmitAction: isEmbedded && ctx.option?.public_key ? renderSubmitAction : null,
    };
}
