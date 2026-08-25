import type { RefObject } from 'react';

import { Button } from '@/components/storefront/button';
import { CloseButton } from '@/components/storefront/close-button';
import { FacetCheckboxGroup } from '@/components/storefront/shop/facet-checkbox-group';
import { FilterGroup } from '@/components/storefront/shop/filter-group';
import { FilterOption } from '@/components/storefront/shop/filter-option';
import { useOverlay } from '@/hooks/storefront/use-overlay';
import type { useShopFilters } from '@/hooks/storefront/use-shop-filters';
import { __, transChoice } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { ShopFacets, ShopPriceBucket } from '@/types';

interface FilterSidebarProps {
    facets: ShopFacets;
    shop: ReturnType<typeof useShopFilters>;
    formatMoney: (value: string | number) => string;
    total: number;
    open: boolean;
    onClose: () => void;
}

function priceBucketLabel(bucket: ShopPriceBucket, formatMoney: (value: string | number) => string): string {
    if (bucket.min === null) {
        return __('Under :amount', { amount: formatMoney(bucket.max ?? '0') });
    }
    if (bucket.max === null) {
        return __('Over :amount', { amount: formatMoney(bucket.min) });
    }

    return `${formatMoney(bucket.min)} – ${formatMoney(bucket.max)}`;
}

export function FilterSidebar({ facets, shop, formatMoney, total, open, onClose }: FilterSidebarProps) {
    const panelRef = useOverlay(open, onClose);

    return (
        <div
            className={cn(
                'group fixed inset-0 z-105 lg:visible lg:static lg:z-auto',
                open ? 'is-open visible' : 'invisible delay-(--duration-slow)',
            )}
        >
            <div
                onClick={onClose}
                className={cn(
                    'absolute inset-0 bg-black/40 transition-opacity duration-(--duration-slow) ease-out-quart lg:hidden',
                    open ? 'opacity-100' : 'opacity-0',
                )}
            />

            <aside
                ref={panelRef as RefObject<HTMLElement>}
                role="dialog"
                aria-modal="true"
                aria-label={__('Filters')}
                className={cn(
                    'absolute inset-y-0 start-0 flex w-full max-w-[320px] flex-col bg-surface shadow-drawer-start transition-[translate] duration-(--duration-slow) ease-out-quart focus:outline-hidden rtl:shadow-drawer-end',
                    'lg:static lg:max-w-none lg:translate-x-0 lg:bg-transparent lg:shadow-none lg:transition-none',
                    open ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full',
                )}
            >
                <div className="flex h-[68px] shrink-0 items-center justify-between border-b border-line px-5 lg:hidden">
                    <h2 className="m-0 font-head text-xl font-semibold text-ink">{__('Filters')}</h2>
                    <CloseButton onClick={onClose} aria-label={__('Close filters')} />
                </div>

                <div className="no-scrollbar flex-1 overflow-y-auto px-5 py-5 lg:overflow-visible lg:p-0">
                    {facets.categories && facets.categories.length > 0 && (
                        <FacetCheckboxGroup
                            title={__('Category')}
                            facets={facets.categories}
                            selected={shop.filters.categories}
                            onToggle={(id) => shop.toggleId('categories', id)}
                        />
                    )}

                    {facets.brands && facets.brands.length > 0 && (
                        <FacetCheckboxGroup
                            title={__('Brand')}
                            facets={facets.brands}
                            selected={shop.filters.brands}
                            onToggle={(id) => shop.toggleId('brands', id)}
                        />
                    )}

                    {facets.price_buckets.length > 0 && (
                        <FilterGroup title={__('Price')}>
                            <FilterOption
                                type="radio"
                                checked={shop.filters.min_price == null && shop.filters.max_price == null}
                                onSelect={() => shop.setPrice(null, null)}
                                label={__('Any price')}
                            />
                            {facets.price_buckets.map((bucket) => (
                                <FilterOption
                                    key={`${bucket.min}-${bucket.max}`}
                                    type="radio"
                                    checked={shop.isPriceActive(bucket.min, bucket.max)}
                                    onSelect={() => shop.setPrice(bucket.min, bucket.max)}
                                    label={priceBucketLabel(bucket, formatMoney)}
                                />
                            ))}
                        </FilterGroup>
                    )}

                    {facets.rating_buckets.length > 0 && (
                        <FilterGroup title={__('Rating')}>
                            <FilterOption
                                type="radio"
                                checked={shop.filters.rating == null}
                                onSelect={() => shop.setRating(null)}
                                label={__('Any rating')}
                            />
                            {facets.rating_buckets.map((value) => (
                                <FilterOption
                                    key={value}
                                    type="radio"
                                    checked={shop.filters.rating === value}
                                    onSelect={() => shop.setRating(value)}
                                    label={
                                        <span className="flex items-center gap-2">
                                            <span className="tracking-star text-amber" aria-hidden="true">
                                                {'★'.repeat(Math.round(value))}
                                            </span>
                                            {__(':rating & up', { rating: value.toFixed(1) })}
                                        </span>
                                    }
                                />
                            ))}
                        </FilterGroup>
                    )}

                    <FilterGroup title={__('Availability')}>
                        <FilterOption
                            type="checkbox"
                            checked={Boolean(shop.filters.in_stock)}
                            onSelect={shop.toggleInStock}
                            label={__('In stock only')}
                        />
                        <FilterOption
                            type="checkbox"
                            checked={Boolean(shop.filters.on_sale)}
                            onSelect={shop.toggleOnSale}
                            label={__('On sale')}
                        />
                    </FilterGroup>
                </div>

                <div className="shrink-0 border-t border-line px-5 py-4 lg:hidden">
                    <Button block onClick={onClose}>
                        {transChoice('Show :count result|Show :count results', total)}
                    </Button>
                </div>
            </aside>
        </div>
    );
}
