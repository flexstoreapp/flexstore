import { Head, router } from '@inertiajs/react';
import { TagIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import * as BrandController from '@/actions/App/Http/Controllers/Admin/BrandController';
import * as BulkBrandController from '@/actions/App/Http/Controllers/Admin/BulkBrandController';
import { ActionBar } from '@/components/admin/action-bar';
import { BrandDialog } from '@/components/admin/brand/brand-dialog';
import { BrandRow } from '@/components/admin/brand/brand-row';
import { useConfirm } from '@/components/admin/confirm';
import { BooleanFilterSelect, FilterBar, FilterButton } from '@/components/admin/filter';
import { Heading } from '@/components/admin/heading';
import { SelectedItemActions } from '@/components/admin/selected-item-actions';
import { TablePagination } from '@/components/admin/table-pagination';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useHotkey } from '@/hooks/admin/use-hotkey';
import { useListManagement } from '@/hooks/admin/use-list-management';
import { useOsDetector } from '@/hooks/admin/use-os-detector';
import { __, transChoice } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Brand, Paginated } from '@/types';

interface BrandListProps {
    brands: Paginated<Brand>;
    filters: {
        query: string | null;
        is_active: boolean | null;
        sort: string;
        direction: string;
    };
}

export default function BrandList({ brands, filters }: BrandListProps) {
    const [brandDialogOpen, setBrandDialogOpen] = useState(false);
    const [selectedBrand, setSelectedBrand] = useState<Brand | undefined>(undefined);
    const isMacOs = useOsDetector() === 'macOS';
    const { confirm } = useConfirm();

    const {
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
        getSortIcon,
        handleSort,
        handleSearchChange,
        handleFilterChange,
        handleResetFilters,
        changePage,
    } = useListManagement({
        data: brands.data,
        filters,
        fetchOnly: ['brands'],
    });

    const isEmpty = brands.data.length === 0 && !hasActiveFilters;

    const handleAddBrand = () => {
        setSelectedBrand(undefined);
        setBrandDialogOpen(true);
    };

    const handleEditBrand = (brand: Brand) => {
        setSelectedBrand(brand);
        setBrandDialogOpen(true);
    };

    const handleBulkDelete = () => {
        if (selectedItems.length === 0) return;

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: transChoice(
                'This will permanently delete :count brand.|This will permanently delete :count brands.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(BulkBrandController.destroy(), {
                        data: { ids: selectedItems },
                        preserveScroll: true,
                        only: ['brands'],
                        onError: (errors) => toast.error(errors.ids),
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    useHotkey(isMacOs ? 'cmd+Backspace' : 'ctrl+Backspace', () => {
        if (selectedItems.length > 0) {
            handleBulkDelete();
        }
    });

    return (
        <>
            <Head title={__('Brands')} />

            <Heading title={__('Brands')} description={__('Manage product brands')}>
                {!isEmpty && (
                    <Can permission={Permission.BrandsManage}>
                        <Button onClick={handleAddBrand}>{__('Add brand')}</Button>
                    </Can>
                )}
            </Heading>

            {isEmpty ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <TagIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No brands')}</EmptyTitle>
                        <EmptyDescription>{__('Start by adding your first brand.')}</EmptyDescription>
                    </EmptyHeader>
                    <Can permission={Permission.BrandsManage}>
                        <EmptyContent>
                            <Button onClick={handleAddBrand}>{__('Add brand')}</Button>
                        </EmptyContent>
                    </Can>
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

                        <Can permissions={[Permission.BrandsManage, Permission.BrandsDelete]}>
                            <SelectedItemActions selectedItems={selectedItems}>
                                <Can permission={Permission.BrandsDelete}>
                                    <Button variant="destructive" onClick={handleBulkDelete}>
                                        {__('Delete')}
                                    </Button>
                                </Can>
                            </SelectedItemActions>
                        </Can>
                    </ActionBar>

                    <FilterBar showFilters={showFilters}>
                        <SearchInput
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            loading={searchLoading}
                        />

                        <BooleanFilterSelect
                            value={filters.is_active}
                            onChange={(value) => handleFilterChange('is_active', value)}
                            allLabel={__('All statuses')}
                            trueLabel={__('Active')}
                            falseLabel={__('Inactive')}
                        />
                    </FilterBar>

                    <ScrollArea className="rounded-xl border shadow-xs">
                        <Table loading={loading}>
                            <TableHeader>
                                <TableRow>
                                    <Can permissions={[Permission.BrandsManage, Permission.BrandsDelete]}>
                                        <TableHead className="w-10" onClick={handleSelectAll}>
                                            <Checkbox checked={checkedState} aria-label={__('Select all')} />
                                        </TableHead>
                                    </Can>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('name')}>
                                        <div className="flex items-center gap-2">
                                            {__('Brand name')} {getSortIcon('name')}
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer text-end"
                                        onClick={() => handleSort('products_count')}
                                    >
                                        <span className="inline-flex items-center">
                                            {__('Products')}
                                            <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                                {getSortIcon('products_count')}
                                            </span>
                                        </span>
                                    </TableHead>
                                    <TableHead>{__('Status')}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {brands.data.map((brand) => (
                                    <BrandRow
                                        key={brand.id}
                                        brand={brand}
                                        isSelected={selectedItems.includes(brand.id)}
                                        onSelectBrand={handleSelectItem}
                                        onEdit={handleEditBrand}
                                    />
                                ))}
                            </TableBody>

                            <TableFooter>
                                <TableRow>
                                    {brands.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={4}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={4}>
                                            <TablePagination
                                                from={brands.from}
                                                to={brands.to}
                                                total={brands.total}
                                                currentPage={brands.current_page}
                                                lastPage={brands.last_page}
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

            <Can permission={Permission.BrandsManage}>
                <BrandDialog open={brandDialogOpen} onOpenChange={setBrandDialogOpen} brand={selectedBrand} />
            </Can>
        </>
    );
}

BrandList.layout = {
    breadcrumbs: [{ title: __('Brands'), href: BrandController.index() }],
};
