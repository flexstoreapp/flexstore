import { Link, usePage } from '@inertiajs/react';
import { memo } from 'react';

import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { StatusBadge } from '@/components/admin/status-badge';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { TableCell, TableRow } from '@/components/ui/table';
import { useFormatTime } from '@/hooks/admin/use-format-time';
import { useRowNavigation } from '@/hooks/admin/use-row-navigation';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { getStatusLabel } from '@/lib/order-utils';
import { Permission } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import type { Order } from '@/types';

interface OrderRowProps {
    order: Order;
    isSelected?: boolean;
    onSelectOrder?: (id: number, shiftKey?: boolean) => void;
}

export const OrderRow = memo(({ order, isSelected = false, onSelectOrder }: OrderRowProps) => {
    const { baseCurrency } = usePage().props;
    const formatDate = useFormatDate();
    const formatTime = useFormatTime();
    const { formatMoney, convertToBaseCurrency } = useFormatMoney();
    const formatId = useFormatId();
    const isDifferentCurrency = order.currency_code && order.currency_code !== baseCurrency;

    const { canNavigate, handleRowClick, handleLinkClick } = useRowNavigation({
        url: OrderController.show(order).url,
        permission: Permission.OrdersView,
    });

    const handleCheckboxClick = (e: React.MouseEvent) => {
        e.stopPropagation();
        if (onSelectOrder) {
            onSelectOrder(order.id, e.shiftKey);
        }
    };

    return (
        <TableRow key={order.id} className={cn(canNavigate && 'cursor-pointer')} onClick={handleRowClick}>
            {onSelectOrder && (
                <TableCell onClick={handleCheckboxClick}>
                    <Checkbox
                        checked={isSelected}
                        aria-label={__('Select order :number', { number: formatId(order.id) })}
                    />
                </TableCell>
            )}
            <TableCell>
                <Can
                    permission={Permission.OrdersView}
                    fallback={<span className="font-medium">{formatId(order.id)}</span>}
                >
                    <Link
                        href={OrderController.show(order)}
                        onClick={handleLinkClick}
                        className="font-medium underline-offset-4 hover:underline"
                        prefetch
                    >
                        {formatId(order.id)}
                    </Link>
                </Can>
            </TableCell>
            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span>{formatDate(order.created_at)}</span>
                    <span className="text-muted-foreground">{formatTime(order.created_at)}</span>
                </div>
            </TableCell>
            <TableCell>
                <div className="flex flex-col gap-0.5">
                    <span>
                        {order.billing_address?.first_name} {order.billing_address?.last_name}
                    </span>
                    <span className="text-muted-foreground">{order.customer_email}</span>
                </div>
            </TableCell>
            <TableCell className="text-end">
                <div className="flex flex-col gap-0.5">
                    <span>{formatMoney(order.total, order.currency_code)}</span>
                    {isDifferentCurrency && (
                        <span className="text-muted-foreground">
                            ≈ {formatMoney(convertToBaseCurrency(order.total, order.exchange_rate), baseCurrency)}
                        </span>
                    )}
                </div>
            </TableCell>
            <TableCell className="text-end">{order.items_sum_quantity}</TableCell>
            <TableCell>
                <StatusBadge status={order.payment_status}>{getStatusLabel(order.payment_status)}</StatusBadge>
            </TableCell>
            <TableCell>
                <StatusBadge status={order.fulfillment_status}>{getStatusLabel(order.fulfillment_status)}</StatusBadge>
            </TableCell>
        </TableRow>
    );
});
OrderRow.displayName = 'OrderRow';
