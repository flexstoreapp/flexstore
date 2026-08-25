import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import type { ProductBuyBoxVariant, ProductDetailData } from '@/types';

function currentVariantId(): string | null {
    return new URLSearchParams(window.location.search).get('variant');
}

function replaceUrlWithVariant(variantId: string): void {
    const params = new URLSearchParams(window.location.search);
    params.set('variant', variantId);
    window.history.replaceState(window.history.state, '', `${window.location.pathname}?${params.toString()}`);
}

export function useVariantUrlSync(
    product: ProductDetailData,
    resolvedVariant: ProductBuyBoxVariant | null,
    selectVariant: (variant: ProductBuyBoxVariant) => void,
): void {
    const selectRef = useRef(selectVariant);
    useEffect(() => {
        selectRef.current = selectVariant;
    });

    const appliedProductId = useRef<number | null>(null);
    useEffect(() => {
        if (appliedProductId.current === product.id) {
            return;
        }
        appliedProductId.current = product.id;

        const variantId = currentVariantId();
        const variant = variantId ? product.variants.find((item) => item.id === variantId) : undefined;

        if (variant) {
            selectRef.current(variant);
        }
    }, [product.id, product.variants]);

    useEffect(() => {
        if (!resolvedVariant) {
            return;
        }

        const sync = () => {
            if (currentVariantId() === resolvedVariant.id) {
                return;
            }

            // history.replaceState keeps the query in the address bar without a
            // client visit. router.replace() re-enters page.set() and can drop
            // Inertia's in-flight deferred reload on same-component navigations.
            replaceUrlWithVariant(resolvedVariant.id);
        };

        sync();

        return router.on('finish', sync);
    }, [resolvedVariant]);
}
