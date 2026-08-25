import { router } from '@inertiajs/react';
import { EllipsisVerticalIcon } from 'lucide-react';

import * as OrderPaymentRecordController from '@/actions/App/Http/Controllers/Admin/OrderPaymentRecordController';
import * as OrderRefundCreditController from '@/actions/App/Http/Controllers/Admin/OrderRefundCreditController';
import VoidPaymentController from '@/actions/App/Http/Controllers/Admin/VoidPaymentController';
import { useConfirm } from '@/components/admin/confirm';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { Permission } from '@/lib/permissions';
import type { Order } from '@/types';

interface OrderPaymentActionsProps {
    order: Order;
    canRecordPayment?: boolean;
    canRefundCredit?: boolean;
}

export function OrderPaymentActions({ order, canRecordPayment, canRefundCredit }: OrderPaymentActionsProps) {
    const { confirm } = useConfirm();
    const { formatMoney } = useFormatMoney();

    const handleRecordPayment = () => {
        confirm({
            title: __('Record payment'),
            description: __('This will record a manual payment of :amount.', {
                amount: formatMoney(order.balance_due_total, order.currency_code),
            }),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(
                        OrderPaymentRecordController.store(order),
                        {},
                        { preserveScroll: true, onFinish: () => resolve() },
                    );
                }),
        });
    };

    const handleVoidPayment = () => {
        confirm({
            title: __('Void payment'),
            description: __('This will reverse all recorded payments for this order.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(VoidPaymentController(order), {}, { preserveScroll: true, onFinish: () => resolve() });
                }),
        });
    };

    const handleRefundCredit = () => {
        confirm({
            title: __('Refund credit'),
            description: __('This will refund :amount to the customer.', {
                amount: formatMoney(order.credit_due_total, order.currency_code),
            }),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(
                        OrderRefundCreditController.store(order),
                        {},
                        { preserveScroll: true, onFinish: () => resolve() },
                    );
                }),
        });
    };

    return (
        <Can permission={Permission.OrdersManage}>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon-sm">
                        <EllipsisVerticalIcon />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    {canRecordPayment && (
                        <DropdownMenuItem onClick={handleRecordPayment}>{__('Record payment')}</DropdownMenuItem>
                    )}
                    {order.is_voidable && (
                        <DropdownMenuItem onClick={handleVoidPayment}>{__('Void payment')}</DropdownMenuItem>
                    )}
                    {canRefundCredit && (
                        <DropdownMenuItem onClick={handleRefundCredit}>{__('Refund credit')}</DropdownMenuItem>
                    )}
                </DropdownMenuContent>
            </DropdownMenu>
        </Can>
    );
}
