import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import { type Crumb } from '@/components/storefront/breadcrumb';
import { LoadMore } from '@/components/storefront/load-more';
import { PageHeader } from '@/components/storefront/page-header';
import { ProductGrid } from '@/components/storefront/product-grid';
import { Section } from '@/components/storefront/section';
import { EmptyState } from '@/components/storefront/shop/empty-state';
import { FilterChips } from '@/components/storefront/shop/filter-chips';
import { FilterSidebar } from '@/components/storefront/shop/filter-sidebar';
import { Pagination } from '@/components/storefront/shop/pagination';
import { Toolbar } from '@/components/storefront/shop/toolbar';
import { useAccumulatedItems } from '@/hooks/storefront/use-accumulated-items';
import { useShopFilters } from '@/hooks/storefront/use-shop-filters';
import { useFormatMoney } from '@/hooks/use-format-money';
import { analytics } from '@/lib/analytics';
import { __, transChoice } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type {
    ListLoadingMethod,
    Paginated,
    ProductData,
    ShopContext,
    ShopFacets,
    ShopFilters,
    SimplePaginated,
    StorefrontSharedData,
} from '@/types';

interface ProductListProps {
    products: Paginated<ProductData> | SimplePaginated<ProductData>;
    facets: ShopFacets;
    filters: ShopFilters;
    loadingMethod: ListLoadingMethod;
    context?: ShopContext;
    searchQuery?: string;
}

function resultsLabel(
    products: Paginated<ProductData> | SimplePaginated<ProductData>,
    loadedCount: number,
    isPaginated: boolean,
): string {
    const total = 'total' in products ? products.total : loadedCount;

    if (total === 0) {
        return __('No results found');
    }

    if (isPaginated && 'total' in products) {
        if (products.last_page <= 1) {
            return transChoice('Showing all :count result|Showing all :count results', products.total, {
                count: products.total,
            });
        }

        return transChoice('Showing :from–:to of :total result|Showing :from–:to of :total results', products.total, {
            from: products.from,
            to: products.to,
            total: products.total,
        });
    }

    return products.next_page_url !== null
        ? transChoice('Showing :count result|Showing :count results', loadedCount, { count: loadedCount })
        : transChoice('Showing all :count result|Showing all :count results', loadedCount, { count: loadedCount });
}

function shopCrumbs(label: string, isUnderShop: boolean): Crumb[] {
    const home = { label: __('Home'), href: HomepageController.show() };
    const shop = { label: __('Shop'), href: ShopController.index() };

    return isUnderShop ? [home, shop, { label }] : [home, { label }];
}

export default function ProductList({
    products,
    facets,
    filters,
    loadingMethod,
    context,
    searchQuery,
}: ProductListProps) {
    const { activeCurrency } = usePage<StorefrontSharedData>().props;
    const shop = useShopFilters(filters);
    const { formatMoney } = useFormatMoney();
    const [filtersOpen, setFiltersOpen] = useState(false);
    const resultsRef = useRef<HTMLDivElement>(null);

    const goToPage = (page: number) => {
        shop.goToPage(page);
        resultsRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const isPaginated = loadingMethod === 'pagination';
    const items = useAccumulatedItems(products, !isPaginated);

    const isSearch = searchQuery !== undefined;
    const crumbLabel = context ? getTranslation(context.name) : isSearch ? __('Search') : __('Shop');
    const heading = isSearch && searchQuery ? __('Search results for ":query"', { query: searchQuery }) : crumbLabel;
    const contextDescription = context ? getTranslation(context.description) : '';
    const total = 'total' in products ? products.total : items.length;
    const productsLabel = resultsLabel(products, items.length, isPaginated);

    const listKey = `${context?.type ?? (isSearch ? 'search' : 'shop')}:${crumbLabel}:${searchQuery ?? ''}`;
    const viewListData = useRef({ items, activeCurrency, listName: heading });
    useEffect(() => {
        viewListData.current = { items, activeCurrency, listName: heading };
    });
    useEffect(() => {
        const data = viewListData.current;
        if (data.items.length > 0) {
            analytics.viewItemList(data.items, data.activeCurrency, undefined, data.listName);
        }
    }, [listKey]);

    return (
        <>
            <PageHeader
                crumbs={shopCrumbs(crumbLabel, Boolean(context) || isSearch)}
                heading={heading}
                subheading={contextDescription || undefined}
            />

            <Section className="mt-6 grid grid-cols-1 items-start gap-8 pb-12 lg:grid-cols-[260px_1fr]">
                <FilterSidebar
                    facets={facets}
                    shop={shop}
                    formatMoney={formatMoney}
                    total={total}
                    open={filtersOpen}
                    onClose={() => setFiltersOpen(false)}
                />

                <div ref={resultsRef} className="min-w-0 scroll-mt-24">
                    <Toolbar
                        resultsLabel={productsLabel}
                        shop={shop}
                        loadingMethod={loadingMethod}
                        hasSearchQuery={Boolean(searchQuery)}
                        onOpenFilters={() => setFiltersOpen(true)}
                    />

                    <FilterChips facets={facets} shop={shop} formatMoney={formatMoney} />

                    {items.length === 0 ? (
                        <EmptyState />
                    ) : (
                        <>
                            <div
                                className={cn(
                                    'transition-opacity',
                                    isPaginated && shop.loading && 'pointer-events-none opacity-50',
                                )}
                            >
                                <ProductGrid products={items} columns={4} className="mt-5" />
                            </div>

                            {isPaginated ? (
                                'last_page' in products && (
                                    <Pagination
                                        currentPage={products.current_page}
                                        lastPage={products.last_page}
                                        onPage={goToPage}
                                    />
                                )
                            ) : (
                                <LoadMore
                                    method={loadingMethod}
                                    hasMore={products.next_page_url !== null}
                                    loading={shop.loading}
                                    onLoadMore={() => shop.goToPage(products.current_page + 1)}
                                />
                            )}
                        </>
                    )}
                </div>
            </Section>
        </>
    );
}
