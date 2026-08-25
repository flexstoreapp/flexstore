import { Head, Link, router } from '@inertiajs/react';
import { TicketIcon } from 'lucide-react';
import { toast } from 'sonner';

import * as BulkCouponController from '@/actions/App/Http/Controllers/Admin/BulkCouponController';
import * as CouponController from '@/actions/App/Http/Controllers/Admin/CouponController';
import { ActionBar } from '@/components/admin/action-bar';
import { useConfirm } from '@/components/admin/confirm';
import { CouponRow } from '@/components/admin/coupon/coupon-row';
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
import type { Coupon, Paginated } from '@/types';

interface CouponListProps {
    coupons: Paginated<Coupon>;
    filters: {
        query: string | null;
        is_active: boolean | null;
        sort: string;
        direction: string;
    };
}

export default function CouponList({ coupons, filters }: CouponListProps) {
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
        data: coupons.data,
        filters,
        fetchOnly: ['coupons'],
    });

    const isEmpty = coupons.data.length === 0 && !hasActiveFilters;

    const handleBulkDelete = () => {
        if (selectedItems.length === 0) return;

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: transChoice(
                'This will permanently delete :count coupon.|This will permanently delete :count coupons.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(BulkCouponController.destroy(), {
                        data: { ids: selectedItems },
                        preserveScroll: true,
                        only: ['coupons'],
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
            <Head title={__('Coupons')} />

            <Heading title={__('Coupons')} description={__('Manage discount coupons and offers')}>
                {!isEmpty && (
                    <Can permission={Permission.CouponsManage}>
                        <Button asChild>
                            <Link href={CouponController.create()} prefetch>
                                {__('Add coupon')}
                            </Link>
                        </Button>
                    </Can>
                )}
            </Heading>

            {isEmpty ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <TicketIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No coupons')}</EmptyTitle>
                        <EmptyDescription>{__('Start by adding your first coupon.')}</EmptyDescription>
                    </EmptyHeader>
                    <Can permission={Permission.CouponsManage}>
                        <EmptyContent>
                            <Button asChild>
                                <Link href={CouponController.create()} prefetch>
                                    {__('Add coupon')}
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
                            onResetFilters={() => handleResetFilters()}
                        />

                        <Can permission={Permission.CouponsDelete}>
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
                                    <Can permission={Permission.CouponsDelete}>
                                        <TableHead className="w-10" onClick={handleSelectAll}>
                                            <Checkbox checked={checkedState} aria-label={__('Select all')} />
                                        </TableHead>
                                    </Can>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('code')}>
                                        <div className="flex items-center gap-2">
                                            {__('Coupon code')} {getSortIcon('code')}
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer text-end"
                                        onClick={() => handleSort('value')}
                                    >
                                        <span className="inline-flex items-center">
                                            {__('Discount value')}
                                            <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                                {getSortIcon('value')}
                                            </span>
                                        </span>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer text-end"
                                        onClick={() => handleSort('used_count')}
                                    >
                                        <span className="inline-flex items-center">
                                            {__('Usage')}
                                            <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                                {getSortIcon('used_count')}
                                            </span>
                                        </span>
                                    </TableHead>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('starts_at')}>
                                        <div className="flex items-center gap-2">
                                            {__('Schedule')} {getSortIcon('starts_at')}
                                        </div>
                                    </TableHead>
                                    <TableHead>{__('Status')}</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {coupons.data.map((coupon) => (
                                    <CouponRow
                                        key={coupon.id}
                                        coupon={coupon}
                                        isSelected={selectedItems.includes(coupon.id)}
                                        onSelectCoupon={handleSelectItem}
                                    />
                                ))}
                            </TableBody>

                            <TableFooter>
                                <TableRow>
                                    {coupons.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={6}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={6}>
                                            <TablePagination
                                                from={coupons.from}
                                                to={coupons.to}
                                                total={coupons.total}
                                                currentPage={coupons.current_page}
                                                lastPage={coupons.last_page}
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

CouponList.layout = {
    breadcrumbs: [{ title: __('Coupons'), href: CouponController.index() }],
};
