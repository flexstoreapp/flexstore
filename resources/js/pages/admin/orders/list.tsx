import { Link } from '@inertiajs/react';
import { ShoppingBagIcon } from 'lucide-react';

import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { ActionBar } from '@/components/admin/action-bar';
import { FilterBar, FilterButton } from '@/components/admin/filter';
import { Heading } from '@/components/admin/heading';
import { OrderRow } from '@/components/admin/order/order-row';
import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { SelectedItemActions } from '@/components/admin/selected-item-actions';
import { TablePagination } from '@/components/admin/table-pagination';
import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { SearchInput } from '@/components/ui/search-input';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useListManagement } from '@/hooks/admin/use-list-management';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Order, Paginated } from '@/types';

interface OrderListProps {
    orders: Paginated<Order>;
    filters: {
        query: string | null;
        fulfillment_status: string | null;
        payment_status: string | null;
        cancellation_status: string | null;
        sort: string;
        direction: string;
    };
}

export default function OrderList({ orders, filters }: OrderListProps) {
    const { open: openProUpgrade } = useProUpgrade();
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
        data: orders.data,
        filters,
        fetchOnly: ['orders'],
    });

    const isEmpty = orders.data.length === 0 && !hasActiveFilters;

    return (
        <>
            <Heading title={__('Orders')} description={__('Manage orders and fulfillment')}>
                {!isEmpty && (
                    <>
                        <Button variant="secondary" onClick={() => openProUpgrade(__('Order CSV export'))}>
                            {__('Export')}
                            <ProBadge />
                        </Button>
                        <Can permission={Permission.OrdersManage}>
                            <Button asChild>
                                <Link href={OrderController.create()} prefetch>
                                    {__('Add order')}
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
                            <ShoppingBagIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No orders')}</EmptyTitle>
                        <EmptyDescription>{__('Orders will appear here once customers place them.')}</EmptyDescription>
                    </EmptyHeader>
                    <Can permission={Permission.OrdersManage}>
                        <EmptyContent>
                            <Button asChild>
                                <Link href={OrderController.create()} prefetch>
                                    {__('Add order')}
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

                        <SelectedItemActions selectedItems={selectedItems} />
                    </ActionBar>

                    <FilterBar showFilters={showFilters}>
                        <SearchInput
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            loading={searchLoading}
                        />

                        <AdaptiveSelect
                            placeholder={__('All payment statuses')}
                            value={filters.payment_status || ''}
                            onValueChange={(value) => handleFilterChange('payment_status', value === '' ? null : value)}
                            options={[
                                { value: 'unpaid', label: __('Unpaid') },
                                { value: 'paid', label: __('Paid') },
                                { value: 'refunded', label: __('Refunded') },
                                { value: 'partially_refunded', label: __('Partially refunded') },
                                { value: 'failed', label: __('Failed') },
                                { value: 'canceled', label: __('Canceled') },
                            ]}
                        />

                        <AdaptiveSelect
                            placeholder={__('All fulfillment statuses')}
                            value={filters.fulfillment_status || ''}
                            onValueChange={(value) =>
                                handleFilterChange('fulfillment_status', value === '' ? null : value)
                            }
                            options={[
                                { value: 'unfulfilled', label: __('Unfulfilled') },
                                { value: 'in_progress', label: __('In progress') },
                                { value: 'fulfilled', label: __('Fulfilled') },
                                { value: 'on_hold', label: __('On hold') },
                            ]}
                        />

                        <AdaptiveSelect
                            placeholder={__('All orders')}
                            value={filters.cancellation_status || ''}
                            onValueChange={(value) =>
                                handleFilterChange('cancellation_status', value === '' ? null : value)
                            }
                            options={[
                                { value: 'active', label: __('Active') },
                                { value: 'canceled', label: __('Canceled') },
                            ]}
                        />
                    </FilterBar>

                    <ScrollArea className="rounded-xl border shadow-xs">
                        <Table loading={loading}>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-10" onClick={handleSelectAll}>
                                        <Checkbox checked={checkedState} aria-label={__('Select all')} />
                                    </TableHead>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('id')}>
                                        <div className="flex items-center gap-2">
                                            {__('Order')} {getSortIcon('id')}
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer"
                                        onClick={() => handleSort('created_at')}
                                    >
                                        <div className="flex items-center gap-2">
                                            {__('Date')} {getSortIcon('created_at')}
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer"
                                        onClick={() => handleSort('customer_name')}
                                    >
                                        <div className="flex items-center gap-2">
                                            {__('Customer')} {getSortIcon('customer_name')}
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer text-end"
                                        onClick={() => handleSort('total')}
                                    >
                                        <span className="inline-flex items-center">
                                            {__('Total')}
                                            <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                                {getSortIcon('total')}
                                            </span>
                                        </span>
                                    </TableHead>
                                    <TableHead className="text-end">{__('Items')}</TableHead>
                                    <TableHead>{__('Payment')}</TableHead>
                                    <TableHead>{__('Fulfillment')}</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {orders.data.map((order) => (
                                    <OrderRow
                                        key={order.id}
                                        order={order}
                                        isSelected={selectedItems.includes(order.id)}
                                        onSelectOrder={handleSelectItem}
                                    />
                                ))}
                            </TableBody>
                            <TableFooter>
                                <TableRow>
                                    {orders.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={8}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={8}>
                                            <TablePagination
                                                from={orders.from}
                                                to={orders.to}
                                                total={orders.total}
                                                currentPage={orders.current_page}
                                                lastPage={orders.last_page}
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

OrderList.layout = {
    breadcrumbs: [{ title: __('Orders'), href: OrderController.index() }],
};
