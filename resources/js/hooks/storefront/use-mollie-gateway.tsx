import { lazy, useEffect, useRef, useState } from 'react';

import type { MollieCardHandlers } from '@/components/storefront/checkout/gateway/mollie-card-element';
import { handlePaymentError } from '@/lib/handle-payment-error';
import { httpPost, isAbortError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import type { GatewayContext, PaymentGatewayAdapter, PaymentOption } from '@/types';

import { useGatewayHandlers } from './use-gateway-handlers';
import { useFormatMoney } from '../use-format-money';

const MollieCardElement = lazy(() =>
    import('@/components/storefront/checkout/gateway/mollie-card-element').then((m) => ({
        default: m.MollieCardElement,
    })),
);

export function useMollieGateway(ctx: GatewayContext): PaymentGatewayAdapter {
    const isEmbedded = ctx.option?.driver === 'mollie' && ctx.option?.checkout_mode === 'embedded';
    const { formatMoney } = useFormatMoney();
    const [processing, setProcessing] = useState(false);
    const { handlersRef, handleReady, ready } = useGatewayHandlers<MollieCardHandlers>(
        isEmbedded,
        Boolean(ctx.option?.profile_id),
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
            const cardToken = await handlersRef.current.createToken();

            const response = await httpPost<{
                mollie_payment_id: string;
                return_url: string;
                cancel_url: string;
            }>(
                checkoutCtx.submitUrl,
                { ...checkoutCtx.formData, card_token: cardToken },
                { signal: abortControllerRef.current.signal },
            );

            cancelUrlRef.current = response.cancel_url ?? null;

            if (!response.return_url) {
                throw new Error(__('Invalid payment response. Please try again.'));
            }

            window.location.href = response.return_url;
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
        if (option.driver !== 'mollie' || option.checkout_mode !== 'embedded' || !option.profile_id) {
            return null;
        }

        return (
            <MollieCardElement
                profileId={option.profile_id}
                testmode={option.testmode ?? false}
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
        renderInlineContent: isEmbedded && ctx.option?.profile_id ? renderInlineContent : null,
        renderHiddenComponent: null,
        renderSubmitAction: null,
    };
}
