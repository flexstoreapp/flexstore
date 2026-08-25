import { XIcon } from 'lucide-react';

import type { useShopFilters } from '@/hooks/storefront/use-shop-filters';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ShopFacets } from '@/types';

interface Chip {
    key: string;
    label: string;
    onRemove: () => void;
}

interface FilterChipsProps {
    facets: ShopFacets;
    shop: ReturnType<typeof useShopFilters>;
    formatMoney: (value: string | number) => string;
}

function priceLabel(min: string | null, max: string | null, formatMoney: (value: string | number) => string): string {
    if (min === null) {
        return __('Under :amount', { amount: formatMoney(max ?? '0') });
    }
    if (max === null) {
        return __('Over :amount', { amount: formatMoney(min) });
    }

    return `${formatMoney(min)} – ${formatMoney(max)}`;
}

export function FilterChips({ facets, shop, formatMoney }: FilterChipsProps) {
    const { filters } = shop;
    const chips: Chip[] = [];

    for (const id of filters.categories) {
        const facet = facets.categories?.find((entry) => entry.id === id);
        chips.push({
            key: `category-${id}`,
            label: facet ? getTranslation(facet.name) : __('Category'),
            onRemove: () => shop.toggleId('categories', id),
        });
    }

    for (const id of filters.brands) {
        const facet = facets.brands?.find((entry) => entry.id === id);
        chips.push({
            key: `brand-${id}`,
            label: facet ? getTranslation(facet.name) : __('Brand'),
            onRemove: () => shop.toggleId('brands', id),
        });
    }

    if (filters.min_price != null || filters.max_price != null) {
        chips.push({
            key: 'price',
            label: priceLabel(filters.min_price, filters.max_price, formatMoney),
            onRemove: () => shop.setPrice(null, null),
        });
    }

    if (filters.rating != null) {
        chips.push({
            key: 'rating',
            label: __(':rating & up', { rating: filters.rating.toFixed(1) }),
            onRemove: () => shop.setRating(null),
        });
    }

    if (filters.in_stock) {
        chips.push({ key: 'in_stock', label: __('In stock only'), onRemove: shop.toggleInStock });
    }

    if (filters.on_sale) {
        chips.push({ key: 'on_sale', label: __('On sale'), onRemove: shop.toggleOnSale });
    }

    if (chips.length === 0) {
        return null;
    }

    return (
        <div className="mt-4 flex flex-wrap items-center gap-2">
            {chips.map((chip) => (
                <button
                    key={chip.key}
                    type="button"
                    onClick={chip.onRemove}
                    className="group flex h-8 items-center gap-1.5 rounded-sm bg-primary-tint ps-3 pe-2.5 text-sm font-semibold text-primary"
                >
                    {chip.label}
                    <XIcon
                        size={12}
                        strokeWidth={2.5}
                        aria-hidden="true"
                        className="text-primary/60 transition group-hover:text-primary"
                    />
                </button>
            ))}
            <button
                type="button"
                onClick={shop.clearAll}
                className="ms-1 text-sm font-semibold text-muted transition-colors hover:text-primary"
            >
                {__('Clear all')}
            </button>
        </div>
    );
}
