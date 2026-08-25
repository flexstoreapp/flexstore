import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import * as CheckoutOptionController from '@/actions/App/Http/Controllers/Storefront/CheckoutOptionController';
import * as CheckoutPaymentOptionController from '@/actions/App/Http/Controllers/Storefront/CheckoutPaymentOptionController';
import { useDebounce } from '@/hooks/use-debounce';
import { httpPost, isAbortError } from '@/lib/http';
import type { PaymentOption } from '@/types';

interface RegionAddress {
    country_code: string;
    state: string;
    postal_code: string;
}

interface UsePaymentOptionsParams {
    hasItems: boolean;
    address: RegionAddress;
    cartSignature: string;
    currentPaymentGatewayId: string;
    setPaymentGatewayId: (id: string) => void;
}

function getPaymentKey(p: UsePaymentOptionsParams) {
    return `${p.address.country_code}|${p.address.state}|${p.address.postal_code}|${p.cartSignature}`;
}

export function usePaymentOptions(params: UsePaymentOptionsParams) {
    const [paymentOptions, setPaymentOptions] = useState<PaymentOption[]>([]);
    const [selectedPaymentOption, setSelectedPaymentOption] = useState<PaymentOption | null>(null);
    const [loading, setLoading] = useState(false);
    const paramsRef = useRef(params);
    useEffect(() => {
        paramsRef.current = params;
    });
    const abortRef = useRef<AbortController | null>(null);
    const lastKeyRef = useRef('');
    const submittingRef = useRef(false);

    const persistPaymentGateway = (id: string) => {
        if (submittingRef.current) return;
        router.post(
            CheckoutOptionController.store(),
            { payment_gateway_id: id },
            { preserveScroll: true, only: ['cart'] },
        );
    };

    const selectPaymentGateway = (id: string) => {
        paramsRef.current.setPaymentGatewayId(id);
        setSelectedPaymentOption(paymentOptions.find((o) => String(o.id) === id) ?? null);
        persistPaymentGateway(id);
    };

    const fetchPayment = useDebounce(() => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        const p = paramsRef.current;
        const key = getPaymentKey(p);
        setLoading(true);

        void httpPost<{ payment: PaymentOption[] }>(
            CheckoutPaymentOptionController.index(),
            { address: p.address.country_code ? p.address : null },
            { signal: controller.signal },
        )
            .then(({ payment }) => {
                lastKeyRef.current = key;
                setPaymentOptions(payment);

                const cur = paramsRef.current;

                if (payment.length === 0) {
                    setSelectedPaymentOption(null);
                    setLoading(false);
                    return;
                }

                const match = payment.find((o) => String(o.id) === cur.currentPaymentGatewayId);
                const selected = match ?? payment[0];
                setSelectedPaymentOption(selected);

                if (String(selected.id) !== cur.currentPaymentGatewayId) {
                    cur.setPaymentGatewayId(String(selected.id));
                    persistPaymentGateway(String(selected.id));
                }

                setLoading(false);
            })
            .catch((error) => {
                if (!isAbortError(error)) {
                    setLoading(false);
                }
            });
    }, 400);

    const key = getPaymentKey(params);

    useEffect(() => {
        if (!params.hasItems) return;
        if (key === lastKeyRef.current) return;
        fetchPayment();
        return () => abortRef.current?.abort();
    }, [params.hasItems, key, fetchPayment]);

    const setSubmitting = (value: boolean) => {
        submittingRef.current = value;
    };

    return { paymentOptions, selectedPaymentOption, loading, selectPaymentGateway, setSubmitting };
}
