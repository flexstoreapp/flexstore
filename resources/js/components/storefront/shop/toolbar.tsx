import { ChevronDownIcon, SlidersHorizontalIcon } from 'lucide-react';
import { Fragment, type ChangeEvent, type ReactNode } from 'react';

import { Button } from '@/components/storefront/button';
import { PER_PAGE_OPTIONS, type useShopFilters } from '@/hooks/storefront/use-shop-filters';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { ListLoadingMethod } from '@/types';

interface ToolbarProps {
    resultsLabel: string;
    shop: ReturnType<typeof useShopFilters>;
    loadingMethod: ListLoadingMethod;
    hasSearchQuery: boolean;
    onOpenFilters: () => void;
}

interface ToolbarSelectProps {
    id: string;
    label: string;
    value: string;
    onChange: (event: ChangeEvent<HTMLSelectElement>) => void;
    children: ReactNode;
}

function ToolbarSelect({ id, label, value, onChange, children }: ToolbarSelectProps) {
    return (
        <div className="relative flex-1 sm:flex-none">
            <label htmlFor={id} className="sr-only">
                {label}
            </label>
            <select
                id={id}
                value={value}
                onChange={onChange}
                className="h-10 w-full appearance-none rounded-md border border-line bg-surface ps-4 pe-9 text-ink transition focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-primary focus-visible:outline-hidden sm:min-w-44"
            >
                {children}
            </select>
            <ChevronDownIcon
                size={13}
                strokeWidth={2.2}
                aria-hidden="true"
                className="pointer-events-none absolute end-3.5 top-1/2 -translate-y-1/2 text-muted"
            />
        </div>
    );
}

export function Toolbar({ resultsLabel, shop, loadingMethod, hasSearchQuery, onOpenFilters }: ToolbarProps) {
    const sortOptions = [
        ...(hasSearchQuery ? [{ value: 'relevance', label: __('Relevance') }] : []),
        { value: 'latest', label: __('Latest') },
        { value: 'price_asc', label: __('Price: Low to high') },
        { value: 'price_desc', label: __('Price: High to low') },
        { value: 'name_asc', label: __('Name: A to Z') },
        { value: 'name_desc', label: __('Name: Z to A') },
    ];

    return (
        <div className="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-md border border-line bg-surface px-4 py-3">
            <Button
                size="sm"
                onClick={onOpenFilters}
                aria-haspopup="dialog"
                aria-expanded={false}
                className="lg:hidden"
            >
                <SlidersHorizontalIcon size={15} strokeWidth={2} aria-hidden="true" />
                {__('Filters')}
                {shop.activeCount > 0 && (
                    <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-white/25 px-1 text-xs">
                        {shop.activeCount}
                    </span>
                )}
            </Button>

            <p className="text-muted" role="status" aria-live="polite">
                {resultsLabel}
            </p>

            <div className="flex w-full flex-wrap items-center gap-x-4 gap-y-3 sm:ms-auto sm:w-auto">
                {loadingMethod !== 'infinite_scroll' && (
                    <div className="-my-1 hidden items-center gap-0.5 text-sm sm:flex">
                        <span className="me-1 text-ink">{__('Show')} :</span>
                        {PER_PAGE_OPTIONS.map((option, index) => (
                            <Fragment key={option}>
                                {index > 0 && (
                                    <span className="text-line-strong" aria-hidden="true">
                                        /
                                    </span>
                                )}
                                <button
                                    type="button"
                                    onClick={() => option !== shop.filters.per_page && shop.setPerPage(option)}
                                    aria-pressed={shop.filters.per_page === option}
                                    className={cn(
                                        'rounded px-2 py-1.5 transition-colors',
                                        shop.filters.per_page === option
                                            ? 'text-ink'
                                            : 'text-muted transition-colors hover:text-primary',
                                    )}
                                >
                                    {option}
                                </button>
                            </Fragment>
                        ))}
                    </div>
                )}

                <ToolbarSelect
                    id="shop-sort"
                    label={__('Sort products')}
                    value={shop.filters.sort}
                    onChange={(event) => shop.setSort(event.target.value)}
                >
                    {sortOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </ToolbarSelect>
            </div>
        </div>
    );
}
