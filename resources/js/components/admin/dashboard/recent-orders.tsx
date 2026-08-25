import { Link } from '@inertiajs/react';

import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import { SectionHeading } from '@/components/admin/section-heading';
import { StatusBadge } from '@/components/admin/status-badge';
import { Can } from '@/components/ui/can';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useRowNavigation } from '@/hooks/admin/use-row-navigation';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { getStatusLabel } from '@/lib/order-utils';
import { Permission } from '@/lib/permissions';
import { cn } from '@/lib/utils';
import type { Order } from '@/types';

export function RecentOrders({ orders }: { orders: Order[] }) {
    const { formatMoney } = useFormatMoney();
    const formatDate = useFormatDate();
    const formatId = useFormatId();

    const { canNavigate, handleRowClick, handleLinkClick } = useRowNavigation<Order>({
        url: (order) => OrderController.show(order).url,
        permission: Permission.OrdersView,
    });

    return (
        <div className="w-full min-w-0 space-y-4 overflow-hidden lg:col-span-4">
            <SectionHeading>{__('Recent orders')}</SectionHeading>
            <ScrollArea className="rounded-xl border shadow-xs">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{__('Order')}</TableHead>
                            <TableHead>{__('Customer')}</TableHead>
                            <TableHead>{__('Fulfillment')}</TableHead>
                            <TableHead className="text-end">{__('Total')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {orders.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={4} className="py-8 text-center text-muted-foreground">
                                    {__('No orders')}
                                </TableCell>
                            </TableRow>
                        ) : (
                            orders.map((order) => (
                                <TableRow
                                    key={order.id}
                                    className={cn(canNavigate && 'cursor-pointer')}
                                    onClick={(e) => handleRowClick(e, order)}
                                >
                                    <TableCell>
                                        <div className="flex flex-col gap-0.5">
                                            <Can
                                                permission={Permission.OrdersView}
                                                fallback={<span className="font-medium">{formatId(order.id)}</span>}
                                            >
                                                <Link
                                                    href={OrderController.show(order)}
                                                    onClick={handleLinkClick}
                                                    className="underline-offset-4 hover:underline"
                                                    prefetch
                                                >
                                                    {formatId(order.id)}
                                                </Link>
                                            </Can>
                                            <span className="text-xs text-muted-foreground">
                                                {formatDate(order.created_at)}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-0.5">
                                            <span className="">
                                                {order.billing_address?.first_name} {order.billing_address?.last_name}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {order.customer_email}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <StatusBadge status={order.fulfillment_status}>
                                            {getStatusLabel(order.fulfillment_status)}
                                        </StatusBadge>
                                    </TableCell>
                                    <TableCell className="text-end">
                                        {formatMoney(order.total, order.currency_code)}
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>
        </div>
    );
}
