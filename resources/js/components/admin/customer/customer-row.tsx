import { Link } from '@inertiajs/react';
import { memo, useMemo } from 'react';

import * as CustomerController from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { TableCell, TableRow } from '@/components/ui/table';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useRowNavigation } from '@/hooks/admin/use-row-navigation';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useInitials } from '@/hooks/use-initials';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Customer } from '@/types';

interface CustomerRowProps {
    customer: Customer;
    isSelected: boolean;
    onSelectCustomer: (customerId: number | string, shiftKey?: boolean) => void;
}

export const CustomerRow = memo(({ customer, isSelected, onSelectCustomer }: CustomerRowProps) => {
    const formatDate = useFormatDate();
    const { formatMoney } = useFormatMoney();
    const { hasPermission } = usePermissions();
    const getInitials = useInitials();
    const canUpdate = hasPermission(Permission.CustomersManage);
    const canDelete = hasPermission(Permission.CustomersDelete);
    const showCheckbox = canUpdate || canDelete;

    const { canNavigate, handleRowClick, handleLinkClick } = useRowNavigation({
        url: CustomerController.edit(customer).url,
        permission: Permission.CustomersManage,
    });

    const handleSelectCustomer = (e: React.MouseEvent) => {
        e.stopPropagation();
        onSelectCustomer(customer.id, e.shiftKey);
    };

    const tableRowClass = useMemo(() => {
        if (!canNavigate) return 'cursor-default';
        return showCheckbox ? '[&>td:not(:first-child)]:cursor-pointer' : 'cursor-pointer';
    }, [canNavigate, showCheckbox]);

    return (
        <TableRow
            key={customer.id}
            data-state={isSelected && 'selected'}
            className={tableRowClass}
            onClick={handleRowClick}
        >
            <Can permission={Permission.CustomersDelete}>
                <TableCell onClick={handleSelectCustomer}>
                    <Checkbox checked={isSelected} aria-label={__('Select :name', { name: customer.name })} />
                </TableCell>
            </Can>
            <TableCell className="flex items-center gap-3">
                <Avatar className="size-8 overflow-hidden rounded-full">
                    <AvatarFallback className="rounded-lg bg-muted">{getInitials(customer.name)}</AvatarFallback>
                </Avatar>
                <div className="flex flex-col gap-0.5">
                    <Can
                        permission={Permission.CustomersManage}
                        fallback={<span className="font-medium">{customer.name}</span>}
                    >
                        <Link
                            href={CustomerController.edit({ customer: customer.id })}
                            onClick={handleLinkClick}
                            className="font-medium underline-offset-4 hover:underline"
                            prefetch
                        >
                            {customer.name}
                        </Link>
                    </Can>
                    <span className="text-muted-foreground">{customer.email}</span>
                </div>
            </TableCell>
            <TableCell className="text-end">{customer.order_count}</TableCell>
            <TableCell className="text-end">{formatMoney(customer.lifetime_value)}</TableCell>
            <TableCell>{customer.last_login_at ? formatDate(customer.last_login_at) : '—'}</TableCell>
        </TableRow>
    );
});
CustomerRow.displayName = 'CustomerRow';
