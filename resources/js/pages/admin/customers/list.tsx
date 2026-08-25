import { Head, Link, router } from '@inertiajs/react';
import { UserRoundIcon } from 'lucide-react';
import { toast } from 'sonner';

import * as BulkCustomerController from '@/actions/App/Http/Controllers/Admin/BulkCustomerController';
import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import * as SettingController from '@/actions/App/Http/Controllers/Admin/SettingController';
import { ActionBar } from '@/components/admin/action-bar';
import { useConfirm } from '@/components/admin/confirm';
import { CustomerRow } from '@/components/admin/customer/customer-row';
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
import type { Paginated, Customer } from '@/types';

interface CustomerListProps {
    customers: Paginated<Customer>;
    filters: {
        query: string | null;
        sort: string;
        direction: string;
    };
}

export default function CustomerList({ customers, filters }: CustomerListProps) {
    const isMacOs = useOsDetector() === 'macOS';
    const { confirm } = useConfirm();

    const {
        selectedItems,
        checkedState,
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
        changePage,
    } = useListManagement({
        data: customers.data,
        filters,
        fetchOnly: ['customers'],
    });

    const isEmpty = customers.data.length === 0 && !hasActiveFilters;

    const handleBulkDelete = () => {
        if (selectedItems.length === 0) return;

        confirm({
            variant: 'delete',
            title: __('Are you absolutely sure?'),
            description: transChoice(
                'This will permanently delete :count customer.|This will permanently delete :count customers.',
                selectedItems.length,
            ),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(BulkCustomerController.destroy(), {
                        data: { ids: selectedItems },
                        preserveScroll: true,
                        only: ['customers'],
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
            <Head title={__('Customers')} />

            <Heading title={__('Customers')} description={__('Manage customer accounts')}>
                {!isEmpty && (
                    <Can permission={Permission.CustomersManage}>
                        <Button asChild>
                            <Link href={CustomerController.create()} prefetch>
                                {__('Add customer')}
                            </Link>
                        </Button>
                    </Can>
                )}
            </Heading>

            {isEmpty ? (
                <Empty>
                    <EmptyHeader>
                        <EmptyMedia variant="icon">
                            <UserRoundIcon />
                        </EmptyMedia>
                        <EmptyTitle>{__('No customers')}</EmptyTitle>
                        <EmptyDescription>{__('Start by adding your first customer.')}</EmptyDescription>
                    </EmptyHeader>
                    <Can permission={Permission.CustomersManage}>
                        <EmptyContent>
                            <Button asChild>
                                <Link href={CustomerController.create()} prefetch>
                                    {__('Add customer')}
                                </Link>
                            </Button>
                        </EmptyContent>
                    </Can>
                </Empty>
            ) : (
                <>
                    <ActionBar>
                        <SearchInput
                            ref={searchInputRef}
                            value={searchQuery}
                            onChange={handleSearchChange}
                            loading={searchLoading}
                            placeholder={__('Search...')}
                            className="w-xs"
                        />

                        <Can permission={Permission.CustomersDelete}>
                            <SelectedItemActions selectedItems={selectedItems}>
                                <Button variant="destructive" onClick={handleBulkDelete}>
                                    {__('Delete')}
                                </Button>
                            </SelectedItemActions>
                        </Can>
                    </ActionBar>

                    <ScrollArea className="rounded-xl border shadow-xs">
                        <Table loading={loading}>
                            <TableHeader>
                                <TableRow>
                                    <Can permission={Permission.CustomersDelete}>
                                        <TableHead className="w-10" onClick={handleSelectAll}>
                                            <Checkbox checked={checkedState} aria-label={__('Select all')} />
                                        </TableHead>
                                    </Can>
                                    <TableHead className="group cursor-pointer" onClick={() => handleSort('name')}>
                                        <div className="flex items-center gap-2">
                                            {__('Customer')} {getSortIcon('name')}
                                        </div>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer text-end"
                                        onClick={() => handleSort('order_count')}
                                    >
                                        <span className="inline-flex items-center">
                                            {__('Total orders')}
                                            <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                                {getSortIcon('order_count')}
                                            </span>
                                        </span>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer text-end"
                                        onClick={() => handleSort('lifetime_value')}
                                    >
                                        <span className="inline-flex items-center">
                                            {__('Lifetime value')}
                                            <span className="w-0 translate-x-2 overflow-visible rtl:-translate-x-2">
                                                {getSortIcon('lifetime_value')}
                                            </span>
                                        </span>
                                    </TableHead>
                                    <TableHead
                                        className="group cursor-pointer"
                                        onClick={() => handleSort('last_login_at')}
                                    >
                                        <div className="flex items-center gap-2">
                                            {__('Last login')} {getSortIcon('last_login_at')}
                                        </div>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {customers.data.map((customer) => (
                                    <CustomerRow
                                        key={customer.id}
                                        customer={customer}
                                        isSelected={selectedItems.includes(customer.id)}
                                        onSelectCustomer={handleSelectItem}
                                    />
                                ))}
                            </TableBody>

                            <TableFooter>
                                <TableRow>
                                    {customers.data.length === 0 ? (
                                        <TableCell className="py-8 text-center text-muted-foreground" colSpan={5}>
                                            {__('No items found.')}
                                        </TableCell>
                                    ) : (
                                        <TableCell colSpan={5}>
                                            <TablePagination
                                                from={customers.from}
                                                to={customers.to}
                                                total={customers.total}
                                                currentPage={customers.current_page}
                                                lastPage={customers.last_page}
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

CustomerList.layout = {
    breadcrumbs: [
        { title: __('Settings'), href: SettingController.index() },
        { title: __('Customers'), href: CustomerController.index() },
    ],
};
