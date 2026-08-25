import { Head } from '@inertiajs/react';
import { BoxesIcon } from 'lucide-react';
import React from 'react';

import * as InventoryController from '@/actions/App/Http/Controllers/Admin/InventoryController';
import { ActionBar } from '@/components/admin/action-bar';
import { BooleanFilterSelect, FilterBar, FilterButton } from '@/components/admin/filter';
import { Heading } from '@/components/admin/heading';
import { InventoryRow } from '@/components/admin/inventory/inventory-row';
import { TablePagination } from '@/components/admin/table-pagination';
import { ThumbnailRatio } from '@/components/admin/thumbnail';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useListManagement } from '@/hooks/admin/use-list-management';
import { __ } from '@/lib/i18n';
import type { Paginated, Product } from '@/types';

interface InventoryListProps {
    products: Paginated<Product>;
    filters: {
        query: string | null;
        low_stock: boolean | null;
        in_stock: boolean | null;
    };
}

export default function InventoryList({ products, filters }: InventoryListProps) {
    const {
        showFilters,
        setShowFilters,
        loading,
        searchLoading,
        searchQuery,
        searchInputRef,
        hasActiveFilters,
        handleSearchChange,
        handleFilterChange,
        handleResetFilters,
        changePage,
    } = useListManagement({
        data: products.data,
        filters,
        fetchOnly: ['products'],
    });

    const isEmpty = products.data.length === 0 && !hasActiveFilters;

    return (
        <>
            <Head title={__('Inventory')} />

            <Heading title={__('Inventory')} description={__('Manage stock levels and track inventory movements')} />

            {isEmpty ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <BoxesIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No inventory')}</EmptyTitle>
                        <EmptyDescription>{__('Add products to start managing inventory.')}</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ) : (
                <>
                    <ActionBar>
                        <FilterButton
                            showFilters={showFilters}
                            setShowFilters={setShowFilters}
                            filters={filters}
                            onResetFilters={handleResetFilters}
                        />
                    </ActionBar>

                    <FilterBar showFilters={showFilters}>
                        <SearchInput
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            loading={searchLoading}
                        />

                        <BooleanFilterSelect
                            value={filters.low_stock}
                            onChange={(value) => handleFilterChange('low_stock', value)}
                            allLabel={__('All products')}
                            trueLabel={__('Low stock only')}
                        />

                        <BooleanFilterSelect
                            value={filters.in_stock}
                            onChange={(value) => handleFilterChange('in_stock', value)}
                            allLabel={__('All stock statuses')}
                            trueLabel={__('In stock')}
                            falseLabel={__('Out of stock')}
                        />
                    </FilterBar>

                    <ScrollArea className="rounded-xl border shadow-xs">
                        <Table loading={loading}>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>{__('Product')}</TableHead>
                                    <TableHead>{__('SKU')}</TableHead>
                                    <TableHead>{__('Stock')}</TableHead>
                                    <TableHead></TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <ThumbnailRatio media={products.data.map((product) => product.featured_media)}>
                                    {products.data.map((product) => {
                                        const trackingVariants = product.variants?.filter((v) => v.track_stock) ?? [];

                                        return (
                                            <React.Fragment key={product.id}>
                                                {(product.track_stock || trackingVariants.length > 0) && (
                                                    <InventoryRow item={product} />
                                                )}
                                                {trackingVariants.map((variant) => (
                                                    <InventoryRow key={variant.id} item={variant} isVariant={true} />
                                                ))}
                                            </React.Fragment>
                                        );
                                    })}
                                </ThumbnailRatio>
                            </TableBody>

                            <TableFooter>
                                <TableRow>
                                    {products.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={4}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={4}>
                                            <TablePagination
                                                from={products.from}
                                                to={products.to}
                                                total={products.total}
                                                currentPage={products.current_page}
                                                lastPage={products.last_page}
                                                onPageChange={changePage}
                                            />
                                        </TableCell>
                                    )}
                                </TableRow>
                            </TableFooter>
                        </Table>

                        <ScrollBar orientation="horizontal" />
                    </ScrollArea>
                </>
            )}
        </>
    );
}

InventoryList.layout = {
    breadcrumbs: [{ title: __('Inventory'), href: InventoryController.index() }],
};
