import { router } from '@inertiajs/react';
import { EllipsisVerticalIcon, TruckIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

import HoldOrderController from '@/actions/App/Http/Controllers/Admin/HoldOrderController';
import InProgressOrderController from '@/actions/App/Http/Controllers/Admin/InProgressOrderController';
import ReleaseOrderHoldController from '@/actions/App/Http/Controllers/Admin/ReleaseOrderHoldController';
import { useConfirm } from '@/components/admin/confirm';
import { LineItemsList, type LineItemRow } from '@/components/admin/line-items-list';
import { StatusBadge } from '@/components/admin/status-badge';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { multiply } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import { mediaSmallThumb } from '@/lib/media';
import type { FulfillmentGroupItem } from '@/lib/order-item-groups';
import { getStatusLabel } from '@/lib/order-utils';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Order } from '@/types';

import { FulfillDialog } from './fulfill-dialog';

interface UnfulfilledCardProps {
    order: Order;
    items: FulfillmentGroupItem[];
}

export function UnfulfilledCard({ order, items }: UnfulfilledCardProps) {
    const { confirm } = useConfirm();
    const { hasPermission } = usePermissions();
    const canFulfill = hasPermission(Permission.OrdersFulfill);
    const canUpdateOrder = hasPermission(Permission.OrdersManage);
    const [fulfillOpen, setFulfillOpen] = useState(false);
    const fulfillableQuantities = useMemo(() => {
        const result: Record<number, number> = {};
        for (const { orderItem, quantity } of items) {
            if (quantity > 0) {
                result[orderItem.id] = quantity;
            }
        }
        return result;
    }, [items]);
    const hasFulfillableItems = Object.keys(fulfillableQuantities).length > 0;
    const isFulfillable = !order.canceled_at && order.fulfillment_status !== 'on_hold';

    const canHold =
        !order.canceled_at &&
        hasFulfillableItems &&
        (order.fulfillment_status === 'unfulfilled' || order.fulfillment_status === 'in_progress');
    const canReleaseHold = !order.canceled_at && order.fulfillment_status === 'on_hold';
    const canMarkInProgress = !order.canceled_at && hasFulfillableItems && order.fulfillment_status === 'unfulfilled';
    const hasActions =
        (canFulfill && isFulfillable && hasFulfillableItems) ||
        (canUpdateOrder && (canHold || canReleaseHold || canMarkInProgress));

    const handleHold = () => {
        confirm({
            title: __('Mark as on hold'),
            description: __('This will put the order on hold and pause fulfillment.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(
                        HoldOrderController.url(order),
                        {},
                        { preserveScroll: true, onFinish: () => resolve() },
                    );
                }),
        });
    };

    const handleInProgress = () => {
        confirm({
            title: __('Mark as in progress'),
            description: __('This will mark the order as in progress.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(
                        InProgressOrderController.url(order),
                        {},
                        { preserveScroll: true, onFinish: () => resolve() },
                    );
                }),
        });
    };

    const handleReleaseHold = () => {
        confirm({
            title: __('Release hold'),
            description: __('This will release the hold and allow the order to be fulfilled.'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.post(
                        ReleaseOrderHoldController.url(order),
                        {},
                        { preserveScroll: true, onFinish: () => resolve() },
                    );
                }),
        });
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>
                        <StatusBadge status={order.fulfillment_status}>
                            {getStatusLabel(order.fulfillment_status)}
                        </StatusBadge>
                    </CardTitle>

                    {order.shipping_carrier_name && (
                        <CardDescription>
                            <div className="flex items-center gap-2">
                                <TruckIcon className="size-4" />
                                <span>{getTranslation(order.shipping_carrier_name)}</span>
                            </div>
                        </CardDescription>
                    )}

                    {hasActions && (
                        <CardAction>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="icon-sm">
                                        <EllipsisVerticalIcon />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <Can permission={Permission.OrdersFulfill}>
                                        {isFulfillable && hasFulfillableItems && (
                                            <DropdownMenuItem onClick={() => setFulfillOpen(true)}>
                                                {__('Mark as fulfilled')}
                                            </DropdownMenuItem>
                                        )}
                                    </Can>
                                    <Can permission={Permission.OrdersManage}>
                                        {canMarkInProgress && (
                                            <DropdownMenuItem onClick={handleInProgress}>
                                                {__('Mark as in progress')}
                                            </DropdownMenuItem>
                                        )}
                                        {canHold && (
                                            <DropdownMenuItem onClick={handleHold}>
                                                {__('Mark as on hold')}
                                            </DropdownMenuItem>
                                        )}
                                        {canReleaseHold && (
                                            <DropdownMenuItem onClick={handleReleaseHold}>
                                                {__('Release hold')}
                                            </DropdownMenuItem>
                                        )}
                                    </Can>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </CardAction>
                    )}
                </CardHeader>

                <CardContent className="space-y-4">
                    <LineItemsList
                        currency={order.currency_code}
                        rows={items.map(({ orderItem, quantity }): LineItemRow => ({
                            key: orderItem.id,
                            thumbnail_url: mediaSmallThumb(orderItem.media),
                            media: orderItem.media,
                            title: getTranslation(orderItem.product_title),
                            variantTitle: orderItem.variant_title,
                            sku: orderItem.product_sku,
                            quantity,
                            unitPrice: orderItem.unit_price,
                            totalPrice: multiply(orderItem.unit_price, quantity),
                        }))}
                    />
                </CardContent>
            </Card>

            {hasFulfillableItems && (
                <FulfillDialog
                    open={fulfillOpen}
                    onOpenChange={setFulfillOpen}
                    order={order}
                    fulfillableQuantities={fulfillableQuantities}
                />
            )}
        </>
    );
}
