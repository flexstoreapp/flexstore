import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import * as CheckoutOptionController from '@/actions/App/Http/Controllers/Storefront/CheckoutOptionController';
import * as CheckoutShippingOptionController from '@/actions/App/Http/Controllers/Storefront/CheckoutShippingOptionController';
import { rateRelevantAddressKey } from '@/hooks/use-address-field-rules';
import { useDebounce } from '@/hooks/use-debounce';
import { httpPost, isAbortError } from '@/lib/http';
import type { ShippingOption } from '@/types';
import type { TaxDetail } from '@/types/tax';

interface CheckoutAddress {
    first_name: string;
    last_name: string;
    address_line_1: string;
    address_line_2: string;
    city: string;
    state: string;
    postal_code: string;
    country_code: string;
    phone: string;
}

interface UseCheckoutOptionsParams {
    hasItems: boolean;
    requiresShipping: boolean;
    addressReady: boolean;
    shippingAddress: CheckoutAddress;
    differentBillingAddress: boolean;
    billingAddress: CheckoutAddress;
    cartSignature: string;
    currentShippingRateId: string;
    setShippingSelection: (rateId: string, quoteReference: string | null) => void;
}

function getFetchKey(p: UseCheckoutOptionsParams) {
    const addressKey = !p.requiresShipping
        ? `billing-only::${rateRelevantAddressKey(p.billingAddress)}`
        : `${rateRelevantAddressKey(p.shippingAddress)}::${p.differentBillingAddress ? rateRelevantAddressKey(p.billingAddress) : ''}`;

    return `${addressKey}::${p.cartSignature}`;
}

export function useCheckoutOptions(params: UseCheckoutOptionsParams) {
    const [shippingOptions, setShippingOptions] = useState<ShippingOption[]>([]);
    const [taxEstimate, setTaxEstimate] = useState('0.0000');
    const [taxDetails, setTaxDetails] = useState<TaxDetail[]>([]);
    const [loading, setLoading] = useState(false);
    const paramsRef = useRef(params);
    useEffect(() => {
        paramsRef.current = params;
    });
    const abortRef = useRef<AbortController | null>(null);
    const lastFetchedKeyRef = useRef('');
    const submittingRef = useRef(false);

    const persistSelection = (data: { shipping_rate_id?: string; shipping_quote_reference?: string }) => {
        if (submittingRef.current) return;
        router.post(CheckoutOptionController.store(), data, {
            preserveScroll: true,
            only: ['cart'],
        });
    };

    const applyShippingOption = (
        option: ShippingOption,
    ): { shipping_rate_id: string; shipping_quote_reference: string } => {
        const rateId = String(option.rate_id ?? option.id);
        const quoteReference = option.quote_reference ?? null;
        paramsRef.current.setShippingSelection(rateId, quoteReference);

        return { shipping_rate_id: rateId, shipping_quote_reference: quoteReference ?? '' };
    };

    const selectShippingRate = (id: string) => {
        const option = shippingOptions.find((o) => String(o.id) === id);
        if (!option) return;
        persistSelection(applyShippingOption(option));
    };

    const fetchOptions = useDebounce(() => {
        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;

        const p = paramsRef.current;
        setLoading(true);

        void httpPost<{
            shipping: ShippingOption[];
            tax_estimate: string;
            tax_details?: TaxDetail[];
        }>(
            CheckoutShippingOptionController.index(),
            p.requiresShipping
                ? {
                      shipping_address: p.shippingAddress,
                      different_billing_address: p.differentBillingAddress,
                      billing_address: p.differentBillingAddress ? p.billingAddress : null,
                  }
                : {
                      shipping_address: null,
                      different_billing_address: false,
                      billing_address: p.billingAddress,
                  },
            { signal: controller.signal },
        )
            .then(({ shipping, tax_estimate, tax_details }) => {
                lastFetchedKeyRef.current = getFetchKey(paramsRef.current);
                setShippingOptions(shipping);
                setTaxEstimate(tax_estimate);
                setTaxDetails(tax_details ?? []);

                const p = paramsRef.current;

                if (shipping.length > 0) {
                    const match = shipping.find((o) => String(o.id) === p.currentShippingRateId);
                    persistSelection(applyShippingOption(match ?? shipping[0]));
                }

                setLoading(false);
            })
            .catch((error) => {
                if (!isAbortError(error)) {
                    setLoading(false);
                }
            });
    }, 400);

    const fetchKey = getFetchKey(params);

    useEffect(() => {
        if (!params.hasItems || !params.addressReady) return;
        if (fetchKey === lastFetchedKeyRef.current) return;
        fetchOptions();
        return () => abortRef.current?.abort();
    }, [params.hasItems, params.addressReady, fetchKey, fetchOptions]);

    const setSubmitting = (value: boolean) => {
        submittingRef.current = value;
    };

    return {
        shippingOptions,
        taxEstimate,
        taxDetails,
        loading,
        selectShippingRate,
        setSubmitting,
    };
}
