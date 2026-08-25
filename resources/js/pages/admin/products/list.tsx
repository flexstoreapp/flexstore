import { Head, Link, router, usePage } from '@inertiajs/react';
import { PackageIcon } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import * as BulkProductController from '@/actions/App/Http/Controllers/Admin/BulkProductController';
import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { ActionBar } from '@/components/admin/action-bar';
import { CategoryPicker, type SelectableItem } from '@/components/admin/category-picker';
import { useConfirm } from '@/components/admin/confirm';
import { BooleanFilterSelect, FilterBar, FilterButton } from '@/components/admin/filter';
import { Heading } from '@/components/admin/heading';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { ProductRow } from '@/components/admin/product/product-row';
import { ResourcePickerTrigger } from '@/components/admin/resource-picker';
import { SelectedItemActions } from '@/components/admin/selected-item-actions';
import { TablePagination } from '@/components/admin/table-pagination';
import { ThumbnailRatio } from '@/components/admin/thumbnail';
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
import { getTranslation } from '@/lib/utils';
import type { Paginated, Product } from '@/types';

interface ProductListProps {
    products: Paginated<Product>;
    filters: {
        query: string | null;
        category: string | null;
        category_name: string | null;
        in_stock: boolean | null;
        is_active: boolean | null;
        sort: string;
        direction: string;
    };
}

export default function ProductList({ products, filters }: ProductListProps) {
    const { open: openProUpgrade } = useProUpgrade();
    const [categoryPickerOpen, setCategoryPickerOpen] = useState(false);
    const { activeLocale } = usePage().props;
    const [filterCategory, setFilterCategory] = useState<SelectableItem | null>(
        getSelectableCategory(filters, activeLocale),
    );
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
        data: products.data,
        filters,
        fetchOnly: ['products'],
    });

    const isEmpty = products.data.length === 0 && !hasActiveFilters;

    const resetFilters = () => {
        setFilterCategory(null);
        handleResetFilters();
    };

    const changeCategory = (category: SelectableItem | null) => {
        setFilterCategory(category);
        handleFilterChange('category', category?.id != null ? String(category.id) : '');
    };

    const handleBulkDelete = () => {
        if (selectedItems.length === 0) return;

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: transChoice(
                'This will permanently delete :count product.|This will permanently delete :count products.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(BulkProductController.destroy(), {
                        data: { ids: selectedItems },
                        preserveScroll: true,
                        only: ['products'],
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
            <Head title={__('Products')} />

            <Heading title={__('Products')} description={__('Manage product catalog')}>
                {!isEmpty && (
                    <>
                        <Button variant="secondary" onClick={() => openProUpgrade(__('Product CSV export'))}>
                            {__('Export')}
                            <ProBadge />
                        </Button>
                        <Can permission={Permission.ProductsManage}>
                            <Button variant="secondary" onClick={() => openProUpgrade(__('Product CSV import'))}>
                                {__('Import')}
                                <ProBadge />
                            </Button>
                        </Can>
                        <Can permission={Permission.ProductsManage}>
                            <Button asChild>
                                <Link href={ProductController.create()} prefetch>
                                    {__('Add product')}
                                </Link>
                            </Button>
                        </Can>
                    </>
                )}
            </Heading>

            {isEmpty ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <PackageIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No products')}</EmptyTitle>
                        <EmptyDescription>{__('Start by adding your first product.')}</EmptyDescription>
                    </EmptyHeader>
                    <Can permission={Permission.ProductsManage}>
                        <EmptyContent>
                            <Button asChild>
                                <Link href={ProductController.create()} prefetch>
                                    {__('Add product')}
                                </Link>
                            </Button>
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
                            onResetFilters={resetFilters}
                        />

                        <Can permission={Permission.ProductsDelete}>
                            <SelectedItemActions selectedItems={selectedItems}>
                                <Button variant="destructive" onClick={handleBulkDelete}>
                                    {__('Delete')}
                                </Button>
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

                        <Can permission={Permission.CategoriesReference}>
                            <ResourcePickerTrigger
                                className="min-w-0 flex-1"
                                label={getTranslation(filterCategory?.name)}
                                placeholder={__('All categories')}
                                onOpen={() => setCategoryPickerOpen(true)}
                            />

                            <CategoryPicker
                                open={categoryPickerOpen}
                                onOpenChange={setCategoryPickerOpen}
                                selectedItems={filterCategory ? [filterCategory] : []}
                                onSelectionChange={changeCategory}
                            />
                        </Can>

                        <BooleanFilterSelect
                            value={filters.in_stock}
                            onChange={(value) => handleFilterChange('in_stock', value)}
                            allLabel={__('All stock statuses')}
                            trueLabel={__('In stock')}
                            falseLabel={__('Out of stock')}
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
                                    <Can permission={Permission.ProductsDelete}>
                                        <TableHead className="w-10" onClick={handleSelectAll}>
                                            <Checkbox checked={checkedState} aria-label={__('Select all')} />
                                        </TableHead>
                                    </Can>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('title')}>
                                        <div className="flex items-center gap-2">
                                            {__('Product')} {getSortIcon('title')}
                                        </div>
                                    </TableHead>
                                    <TableHead>{__('Category')}</TableHead>
                                    <TableHead
                                        className="group cursor-pointer text-end"
                                        onClick={() => handleSort('price')}
                                    >
                                        <span className="inline-flex items-center">
                                            {__('Price')}
                                            <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                                {getSortIcon('price')}
                                            </span>
                                        </span>
                                    </TableHead>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('stock')}>
                                        <div className="flex items-center gap-2">
                                            {__('Stock')} {getSortIcon('stock')}
                                        </div>
                                    </TableHead>
                                    <TableHead>{__('Status')}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <ThumbnailRatio media={products.data.map((product) => product.featured_media)}>
                                    {products.data.map((product) => (
                                        <ProductRow
                                            key={product.id}
                                            product={product}
                                            isSelected={selectedItems.includes(product.id)}
                                            onSelectProduct={handleSelectItem}
                                        />
                                    ))}
                                </ThumbnailRatio>
                            </TableBody>

                            <TableFooter>
                                <TableRow>
                                    {products.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={6}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={6}>
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

function getSelectableCategory(filters: ProductListProps['filters'], activeLocale: string): SelectableItem | null {
    if (!filters.category) return null;

    return { id: Number(filters.category), name: { [activeLocale]: filters.category_name || '' } };
}

ProductList.layout = {
    breadcrumbs: [{ title: __('Products'), href: ProductController.index() }],
};
