import { useEffect, useRef } from 'react';

import { analytics } from '@/lib/analytics';
import type { ProductBuyBoxVariant, ProductDetailData } from '@/types';

export function useTrackProductView(
    product: ProductDetailData,
    price: string | null,
    currency: string,
    variant: ProductBuyBoxVariant | null,
): void {
    const latest = useRef({ product, price, currency, variant });
    useEffect(() => {
        latest.current = { product, price, currency, variant };
    });

    const tracked = useRef(false);
    useEffect(() => {
        if (tracked.current) {
            return;
        }
        tracked.current = true;

        const view = latest.current;
        analytics.viewItem(view.product, view.price, view.currency, view.variant);
    }, [product.id]);
}
