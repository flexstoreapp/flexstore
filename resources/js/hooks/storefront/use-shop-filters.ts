import { router, usePage } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';

import type { ShopFilters } from '@/types';

export const PER_PAGE_OPTIONS = [24, 36, 48, 60];

function buildParams(filters: ShopFilters): Record<string, string> {
    const params: Record<string, string> = {};

    if (filters.query) {
        params.query = filters.query;
    }
    if (filters.categories.length > 0) {
        params.categories = filters.categories.join(',');
    }
    if (filters.brands.length > 0) {
        params.brands = filters.brands.join(',');
    }
    if (filters.min_price != null) {
        params.min_price = filters.min_price;
    }
    if (filters.max_price != null) {
        params.max_price = filters.max_price;
    }
    if (filters.in_stock) {
        params.in_stock = '1';
    }
    if (filters.on_sale) {
        params.on_sale = '1';
    }
    if (filters.rating != null) {
        params.rating = String(filters.rating);
    }
    if (filters.sort) {
        params.sort = filters.sort;
    }
    if (PER_PAGE_OPTIONS.includes(filters.per_page)) {
        params.per_page = String(filters.per_page);
    }

    return params;
}

export function useShopFilters(serverFilters: ShopFilters) {
    const basePath = usePage().url.split('?')[0];

    const [filters, setFilters] = useState(serverFilters);
    const [syncedFrom, setSyncedFrom] = useState(serverFilters);
    const [loading, setLoading] = useState(false);
    const inFlight = useRef(0);

    if (serverFilters !== syncedFrom) {
        setSyncedFrom(serverFilters);
        setFilters(serverFilters);
    }

    const navigate = useCallback(
        (next: ShopFilters, page?: number) => {
            setFilters(next);

            const params = buildParams(next);

            if (page && page > 1) {
                params.page = String(page);
            }

            router.get(basePath, params, {
                preserveScroll: true,
                preserveState: true,
                onStart: () => {
                    inFlight.current += 1;
                    setLoading(true);
                },
                onFinish: () => {
                    inFlight.current = Math.max(inFlight.current - 1, 0);
                    if (inFlight.current === 0) {
                        setLoading(false);
                    }
                },
            });
        },
        [basePath],
    );

    const patch = useCallback(
        (changes: Partial<ShopFilters>) => navigate({ ...filters, ...changes }),
        [filters, navigate],
    );

    const toggleId = useCallback(
        (key: 'categories' | 'brands', id: number) => {
            const current = filters[key];
            const next = current.includes(id) ? current.filter((value) => value !== id) : [...current, id];

            patch({ [key]: next });
        },
        [filters, patch],
    );

    const setPrice = useCallback(
        (min: string | null, max: string | null) => patch({ min_price: min, max_price: max }),
        [patch],
    );

    const isPriceActive = useCallback(
        (min: string | null, max: string | null) => filters.min_price === min && filters.max_price === max,
        [filters],
    );

    const clearAll = useCallback(
        () =>
            navigate({
                query: null,
                categories: [],
                brands: [],
                min_price: null,
                max_price: null,
                in_stock: null,
                on_sale: null,
                rating: null,
                sort: filters.sort,
                per_page: filters.per_page,
            }),
        [navigate, filters.sort, filters.per_page],
    );

    const goToPage = useCallback((page: number) => navigate(filters, page), [filters, navigate]);

    const activeCount =
        filters.categories.length +
        filters.brands.length +
        (filters.min_price != null || filters.max_price != null ? 1 : 0) +
        (filters.rating != null ? 1 : 0) +
        (filters.in_stock ? 1 : 0) +
        (filters.on_sale ? 1 : 0);

    return {
        filters,
        patch,
        toggleId,
        setPrice,
        isPriceActive,
        setRating: (rating: number | null) => patch({ rating }),
        toggleInStock: () => patch({ in_stock: !filters.in_stock }),
        toggleOnSale: () => patch({ on_sale: !filters.on_sale }),
        setSort: (sort: string) => patch({ sort }),
        setPerPage: (perPage: number) => patch({ per_page: perPage }),
        clearAll,
        goToPage,
        activeCount,
        loading,
    };
}
