import { usePage } from '@inertiajs/react';
import { useState } from 'react';

import { useAddToCart } from '@/hooks/storefront/use-add-to-cart';
import { analytics } from '@/lib/analytics';
import { __ } from '@/lib/i18n';
import { resolveProductPricing } from '@/lib/product-pricing';
import type { ProductBuyBoxData, ProductBuyBoxVariant, StorefrontSharedData } from '@/types';

export type ProductPurchase = ReturnType<typeof useProductPurchase>;

function resolveMaxQuantity(data: ProductBuyBoxData, variant: ProductBuyBoxVariant | null): number | undefined {
    const stockMax = variant ? variant.max_quantity : data.has_variants ? null : data.max_quantity;
    const flashCaps: (number | null)[] = [];
    const caps = [stockMax, ...flashCaps].filter((cap): cap is number => cap !== null);

    return caps.length > 0 ? Math.min(...caps) : undefined;
}

function defaultSelection(data: ProductBuyBoxData): Record<string, string> {
    const variant = data.variants.find((item) => item.is_default);

    return variant ? { ...variant.option_values } : {};
}

export function useProductPurchase(data: ProductBuyBoxData, onClose: () => void) {
    const { activeCurrency } = usePage<StorefrontSharedData>().props;
    const cart = useAddToCart();
    const [selected, setSelected] = useState<Record<string, string>>(() => defaultSelection(data));
    const [quantity, setQuantity] = useState(1);

    const findVariant = (selection: Record<string, string>): ProductBuyBoxVariant | null => {
        if (!data.has_variants || Object.keys(selection).length !== data.options.length) {
            return null;
        }
        return (
            data.variants.find((variant) =>
                data.options.every((option) => variant.option_values[option.id] === selection[option.id]),
            ) ?? null
        );
    };

    const resolvedVariant = findVariant(selected);
    const pricing = resolveProductPricing(data, resolvedVariant);
    const maxQuantity = resolveMaxQuantity(data, resolvedVariant);

    const inStock = resolvedVariant ? resolvedVariant.in_stock : data.in_stock;
    const needsSelection = data.has_variants && !resolvedVariant;

    const busy = cart.status === 'loading' || cart.buyingNow;
    const canPurchase = inStock && !needsSelection && !busy && cart.status !== 'error';
    const cartInput = { productId: data.id, variantId: resolvedVariant?.id, quantity };

    const statusLabel = { idle: __('Add to cart'), loading: __('Adding'), success: __('Added'), error: __('Failed') }[
        cart.status
    ];
    const addToCartLabel = needsSelection ? __('Select options') : !inStock ? __('Out of stock') : statusLabel;

    const submitAddToCart = () => {
        if (canPurchase) {
            cart.addToCart(cartInput, {
                resetSuccessAfter: 1500,
                onSuccess: () => analytics.addToCart(data, pricing.price, quantity, activeCurrency, resolvedVariant),
            });
        }
    };

    const submitBuyNow = () => {
        if (canPurchase) {
            cart.buyNow(cartInput);
            onClose();
        }
    };

    const applySelection = (next: Record<string, string>) => {
        setSelected(next);
        const variantMax = findVariant(next)?.max_quantity;
        if (variantMax !== null && variantMax !== undefined && quantity > variantMax) {
            setQuantity(Math.max(1, variantMax));
        }
        cart.reset();
    };

    const selectOption = (optionId: string, valueId: string) => applySelection({ ...selected, [optionId]: valueId });

    const selectVariant = (variant: ProductBuyBoxVariant) => applySelection({ ...variant.option_values });

    const changeQuantity = (value: number) => {
        setQuantity(value);
        cart.reset();
    };

    return {
        cart,
        selected,
        selectOption,
        selectVariant,
        resolvedVariant,
        pricing,
        canPurchase,
        quantity,
        changeQuantity,
        maxQuantity,
        addToCartLabel,
        submitAddToCart,
        submitBuyNow,
    };
}
