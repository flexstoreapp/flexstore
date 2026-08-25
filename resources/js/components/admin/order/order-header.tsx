import { useState } from 'react';

import * as DuplicateOrderController from '@/actions/App/Http/Controllers/Admin/DuplicateOrderController';
import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import OrderInvoiceController from '@/actions/App/Http/Controllers/Admin/OrderInvoiceController';
import * as OrderRefundController from '@/actions/App/Http/Controllers/Admin/OrderRefundController';
import { Heading } from '@/components/admin/heading';
import { OrderTitle } from '@/components/admin/order/order-title';
import { ResponsiveActions, type ResponsiveAction } from '@/components/admin/responsive-actions';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Order } from '@/types';

import { CancelOrderDialog } from './cancel-order-dialog';

export function OrderHeader({ order }: { order: Order }) {
    const formatDate = useFormatDate();
    const formatId = useFormatId();
    const { hasPermission } = usePermissions();
    const [cancelOpen, setCancelOpen] = useState(false);

    const canManage = hasPermission(Permission.OrdersManage);
    const canRefund = hasPermission(Permission.OrdersRefund) && order.is_refundable;
    const canCancel = hasPermission(Permission.OrdersCancel) && order.is_cancellable;

    const actions: ResponsiveAction[] = [];

    actions.push({ key: 'invoice', label: __('Invoice'), href: OrderInvoiceController(order).url, external: true });

    if (canRefund) {
        actions.push({ key: 'refund', label: __('Refund'), href: OrderRefundController.create(order), prefetch: true });
    }

    if (canManage) {
        actions.push({
            key: 'duplicate',
            label: __('Duplicate'),
            href: DuplicateOrderController.create(order),
            alwaysOverflow: true,
        });
    }

    if (canCancel) {
        actions.push({
            key: 'cancel',
            label: __('Cancel order'),
            onClick: () => setCancelOpen(true),
            destructive: true,
            alwaysOverflow: true,
        });
    }

    if (canManage) {
        actions.push({
            key: 'edit',
            label: __('Edit order'),
            href: OrderController.edit(order),
            prefetch: true,
            variant: 'default',
            pinned: true,
        });
    }

    return (
        <>
            <Heading
                pageTitle={`${formatId(order.id)} - ${__('Orders')}`}
                title={<OrderTitle order={order} />}
                description={__('Order placed on :date', {
                    date: formatDate(order.created_at, {
                        hour: '2-digit',
                        minute: '2-digit',
                    }),
                })}
                backHref={OrderController.index()}
            >
                <ResponsiveActions actions={actions} />
            </Heading>

            <CancelOrderDialog open={cancelOpen} onOpenChange={setCancelOpen} order={order} />
        </>
    );
}
