import { router } from '@inertiajs/react';
import { ArrowDownIcon, ArrowUpIcon, ArrowUpDownIcon } from 'lucide-react';
import { createElement, useEffect, useMemo, useRef, useState } from 'react';

import { useShiftSelect } from '@/hooks/admin/use-shift-select';
import { useDebounce } from '@/hooks/use-debounce';

interface UseListManagementOptions {
    data: { id: unknown }[];
    filters: Record<string, unknown>;
    fetchOnly: string[];
}

export function useListManagement({ data, filters, fetchOnly }: UseListManagementOptions) {
    const [selectedItems, setSelectedItems] = useState<(number | string)[]>([]);
    const [showFilters, setShowFilters] = useState(false);
    const [loading, setLoading] = useState(false);
    const [searchLoading, setSearchLoading] = useState(false);
    const [searchQuery, setSearchQuery] = useState((filters.query as string) ?? '');
    const [prevData, setPrevData] = useState(data);
    const searchInputRef = useRef<HTMLInputElement>(null);

    if (data !== prevData) {
        setPrevData(data);
        setSelectedItems([]);
    }

    const checkedState: boolean | 'indeterminate' = useMemo(() => {
        if (data.length === 0) return false;
        if (selectedItems.length === 0) return false;
        if (selectedItems.length === data.length) return true;
        return 'indeterminate';
    }, [data, selectedItems]);

    const hasActiveFilters = useMemo(() => {
        return Object.entries(filters).some(([key, value]) => {
            if (['category_name', 'sort', 'direction', 'page'].includes(key)) {
                return false;
            }
            return value !== '' && value !== null && value !== undefined;
        });
    }, [filters]);

    useEffect(() => {
        if (showFilters && searchInputRef.current) {
            searchInputRef.current?.focus();
        }
    }, [showFilters]);

    const handleSelectAll = () => {
        if (checkedState === true || checkedState === 'indeterminate') {
            setSelectedItems([]);
        } else {
            setSelectedItems(data.map((item) => item.id as number | string));
        }
    };

    const { getRange } = useShiftSelect();

    const handleSelectItem = (itemId: number | string, shiftKey = false) => {
        const currentIndex = data.findIndex((item) => item.id === itemId);
        const range = getRange(currentIndex, shiftKey);

        if (range) {
            const rangeIds = data.slice(range[0], range[1] + 1).map((item) => item.id as number | string);
            setSelectedItems((prev) => [...new Set([...prev, ...rangeIds])]);
        } else {
            setSelectedItems((prev) => {
                if (prev.includes(itemId)) {
                    return prev.filter((id) => id !== itemId);
                } else {
                    return [...prev, itemId];
                }
            });
        }
    };

    const fetchItems = (updatedFilters: typeof filters, onSuccess = () => {}) => {
        setLoading(true);

        const queryParams: Record<string, string | number | boolean> = {};

        Object.entries(updatedFilters).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                queryParams[key] = value as string | number | boolean;
            }
        });

        const currentUrl = window.location.origin + window.location.pathname;
        router.get(currentUrl, queryParams, {
            preserveScroll: true,
            preserveState: true,
            only: fetchOnly,
            onSuccess: () => {
                setLoading(false);
                onSuccess();
            },
        });
    };

    const isSorted = (field: string): boolean => {
        return filters.sort === field;
    };

    const getSortIcon = (field: string) => {
        const sorted = isSorted(field);
        const iconClassName = sorted ? 'size-4' : 'size-4 opacity-0 group-hover:opacity-100 transition-opacity';

        if (!sorted) {
            return createElement(ArrowUpDownIcon, { className: iconClassName });
        }

        return filters.direction === 'asc'
            ? createElement(ArrowUpIcon, { className: iconClassName })
            : createElement(ArrowDownIcon, { className: iconClassName });
    };

    const handleSort = (field: string) => {
        const direction = filters.sort === field && filters.direction === 'asc' ? 'desc' : 'asc';
        fetchItems({ ...filters, sort: field, direction });
    };

    const search = useDebounce((value: string) => {
        setSearchLoading(true);
        fetchItems({ ...filters, query: value }, () => setSearchLoading(false));
    }, 300);

    const handleSearchChange = (value: string) => {
        setSearchQuery(value);
        search(value);
    };

    const normalizeFilterValue = (value: unknown) => (typeof value === 'boolean' ? (value ? '1' : '0') : value);

    const handleFilterChange = (field: string, value: unknown) => {
        fetchItems({ ...filters, [field]: normalizeFilterValue(value) });
    };

    const handleFilterChanges = (updates: Record<string, unknown>) => {
        const merged: Record<string, unknown> = { ...filters };
        for (const [key, value] of Object.entries(updates)) {
            merged[key] = normalizeFilterValue(value);
        }
        fetchItems(merged);
    };

    const handleResetFilters = () => {
        fetchItems({ sort: filters.sort, direction: filters.direction }, () => {
            setShowFilters(false);
            setSearchQuery('');
        });
    };

    const changePage = (page: number) => {
        fetchItems({ page });
    };

    return {
        selectedItems,
        checkedState,
        showFilters,
        setShowFilters,
        loading,
        searchLoading,
        searchQuery,
        searchInputRef,
        hasActiveFilters,
        handleSelectAll,
        handleSelectItem,
        isSorted,
        getSortIcon,
        handleSort,
        handleSearchChange,
        handleFilterChange,
        handleFilterChanges,
        handleResetFilters,
        changePage,
    };
}
