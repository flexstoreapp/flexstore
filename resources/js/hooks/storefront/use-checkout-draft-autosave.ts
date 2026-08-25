import { useCallback, useEffect, useRef } from 'react';

import * as CheckoutDraftController from '@/actions/App/Http/Controllers/Storefront/CheckoutDraftController';
import { httpPatch, isAbortError, isHttpError } from '@/lib/http';
import type { AddressFormValues } from '@/types';

interface UseCheckoutDraftAutosaveParams {
    enabled: boolean;
    requiresShipping: boolean;
    email: string;
    shippingAddress: AddressFormValues;
    differentBillingAddress: boolean;
    billingAddress: AddressFormValues;
    notes: string;
    customerAddressId: number | null;
    onCouponRemoved?: () => void;
    onSaved?: () => void;
}

export function useCheckoutDraftAutosave(params: UseCheckoutDraftAutosaveParams) {
    const abortRef = useRef<AbortController | null>(null);
    const lastSentRef = useRef<string>('');
    const paramsRef = useRef(params);

    useEffect(() => {
        paramsRef.current = params;
    });

    const cancel = useCallback(() => {
        abortRef.current?.abort();
    }, []);

    const save = useCallback(() => {
        const p = paramsRef.current;
        if (!p.enabled) return;
        if (!p.email || !p.email.includes('@')) return;

        const payload = p.requiresShipping
            ? {
                  customer_email: p.email,
                  shipping_address: p.shippingAddress,
                  different_billing_address: p.differentBillingAddress,
                  billing_address: p.differentBillingAddress ? p.billingAddress : null,
                  notes: p.notes,
                  customer_address_id: p.customerAddressId,
              }
            : {
                  customer_email: p.email,
                  shipping_address: null,
                  different_billing_address: false,
                  billing_address: p.billingAddress,
                  notes: p.notes,
                  customer_address_id: p.customerAddressId,
              };
        const serialized = JSON.stringify(payload);

        if (serialized === lastSentRef.current) return;
        lastSentRef.current = serialized;
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        httpPatch<{ coupon_removed?: boolean }>(CheckoutDraftController.update(), payload, {
            signal: controller.signal,
        })
            .then((result) => {
                if (result?.coupon_removed) {
                    p.onCouponRemoved?.();
                }
                p.onSaved?.();
            })
            .catch((error) => {
                if (isAbortError(error)) {
                    return;
                }

                if (isHttpError(error) && error.status === 429) {
                    return;
                }

                lastSentRef.current = '';
                console.error('Checkout draft save failed:', error);
            });
    }, []);

    const flush = useCallback(() => {
        save();
    }, [save]);

    useEffect(() => {
        const onHidden = () => {
            if (document.visibilityState === 'hidden') flush();
        };

        window.addEventListener('pagehide', flush);
        document.addEventListener('visibilitychange', onHidden);

        return () => {
            window.removeEventListener('pagehide', flush);
            document.removeEventListener('visibilitychange', onHidden);
            flush();
        };
    }, [flush]);

    return { cancel, flush };
}
