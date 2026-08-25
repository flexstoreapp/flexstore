import type { FormDataConvertible } from '@inertiajs/core';
import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { __ } from '@/lib/i18n';
import type { GatewayContext, PaymentGatewayAdapter } from '@/types';

import { useFormatMoney } from '../use-format-money';

export function useDefaultGateway(ctx: GatewayContext): PaymentGatewayAdapter {
    const isCod = ctx.option?.driver === 'cod';
    const isFree = ctx.option === null;
    const { formatMoney } = useFormatMoney();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const processingRef = useRef(false);

    const ctxRef = useRef(ctx);
    useEffect(() => {
        ctxRef.current = ctx;
    });

    return {
        processing,
        ready: true,
        error,
        replacesSubmitButton: false,
        submitButtonText:
            isCod || isFree
                ? __('Place order')
                : __('Pay :amount', { amount: formatMoney(ctx.amount, ctx.currencyCode) }),
        handleSubmit: async () => {
            if (processingRef.current) {
                return;
            }

            processingRef.current = true;
            const gatewayCtx = ctxRef.current;
            gatewayCtx.setSubmitting(true);
            setProcessing(true);
            setError(null);
            router.post(gatewayCtx.submitUrl, gatewayCtx.formData as Record<string, FormDataConvertible>, {
                preserveScroll: (page) => page.component === gatewayCtx.pageComponent,
                onError: (errors) => {
                    if (errors.payment) {
                        setError(errors.payment);
                    }

                    for (const [key, message] of Object.entries(errors)) {
                        gatewayCtx.setFormError(key, message);
                    }

                    if (Object.keys(errors).length > 0) {
                        gatewayCtx.scrollToFirstError();
                    }
                },
                onFinish: () => {
                    processingRef.current = false;
                    setProcessing(false);
                    gatewayCtx.setSubmitting(false);
                },
            });
        },
        renderInlineContent: null,
        renderHiddenComponent: null,
        renderSubmitAction: null,
    };
}
