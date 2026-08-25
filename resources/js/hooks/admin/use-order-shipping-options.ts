import { useEffect, useMemo, useRef, useState } from 'react';

import OrderShippingOptionController from '@/actions/App/Http/Controllers/Admin/OrderShippingOptionController';
import { httpPost } from '@/lib/http';
import type { OrderAddress, ShippingOption, TranslatableField } from '@/types';

export interface LockedShippingRate {
    name: TranslatableField | null;
    provider: string | null;
    carrierName: TranslatableField | null;
    serviceCode: string | null;
    total: string;
}

interface UseOrderShippingOptionsParams {
    items: Array<{ product_id: string; product_variant_id?: string; quantity: string }>;
    shippingAddress: OrderAddress | null;
    differentBillingAddress: boolean;
    billingAddress: OrderAddress | null;
    addressReady: boolean;
    addressKey: string;
    lockedRate?: LockedShippingRate | null;
    onShippingRateChange: (rateId: string, rate: string) => void;
}

export function useOrderShippingOptions({
    items,
    shippingAddress,
    differentBillingAddress,
    billingAddress,
    addressReady,
    addressKey,
    lockedRate,
    onShippingRateChange,
}: UseOrderShippingOptionsParams) {
    const [shippingOptions, setShippingOptions] = useState<ShippingOption[]>([]);
    const [loading, setLoading] = useState(false);

    const canFetchShipping = useMemo(() => {
        if (lockedRate) return false;
        if (!items.length) return false;
        if (!addressReady) return false;
        return true;
    }, [lockedRate, items.length, addressReady]);

    const [prevCanFetch, setPrevCanFetch] = useState(canFetchShipping);
    if (canFetchShipping !== prevCanFetch) {
        setPrevCanFetch(canFetchShipping);
        if (canFetchShipping) {
            setLoading(true);
        } else {
            setShippingOptions([]);
        }
    }

    const requestKeyRef = useRef(0);

    const fetchKey = useMemo(
        () => JSON.stringify({ canFetchShipping, items, addressKey }),
        [canFetchShipping, items, addressKey],
    );

    const [prevFetchKey, setPrevFetchKey] = useState(fetchKey);
    if (fetchKey !== prevFetchKey) {
        setPrevFetchKey(fetchKey);
        if (canFetchShipping) {
            setLoading(true);
        }
    }

    const prevFetchKeyRef = useRef<string | undefined>(undefined);
    useEffect(() => {
        const prev = prevFetchKeyRef.current;
        prevFetchKeyRef.current = fetchKey;

        if ((prev && prev === fetchKey) || !canFetchShipping) return;

        const requestKey = ++requestKeyRef.current;

        httpPost<ShippingOption[]>(OrderShippingOptionController(), {
            items: items.map((item) => ({
                product_id: parseInt(item.product_id),
                product_variant_id: item.product_variant_id || null,
                quantity: parseInt(item.quantity),
            })),
            shipping_address: shippingAddress,
            different_billing_address: differentBillingAddress,
            billing_address: differentBillingAddress ? billingAddress : null,
        })
            .then((data) => {
                if (requestKey !== requestKeyRef.current) return;

                setShippingOptions(data);

                if (data.length > 0) {
                    const first = data[0];
                    onShippingRateChange(String(first.id), first.rate);
                } else {
                    onShippingRateChange('', '0.0000');
                }
                setLoading(false);
            })
            .catch((error) => {
                if (requestKey !== requestKeyRef.current) return;

                console.error('Failed to fetch shipping options:', error);
                setShippingOptions([]);
                onShippingRateChange('', '0.0000');
                setLoading(false);
            });
    }, [
        fetchKey,
        canFetchShipping,
        items,
        shippingAddress,
        differentBillingAddress,
        billingAddress,
        onShippingRateChange,
    ]);

    const selectShippingRate = (rateId: string) => {
        const option = shippingOptions.find((o) => String(o.id) === rateId);
        if (option) {
            onShippingRateChange(rateId, option.rate);
        }
    };

    return { shippingOptions, loading, canFetchShipping, selectShippingRate };
}
