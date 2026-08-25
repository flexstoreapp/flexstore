import { router } from '@inertiajs/react';
import { EllipsisVerticalIcon, TruckIcon } from 'lucide-react';
import { useState } from 'react';

import * as OrderShipmentController from '@/actions/App/Http/Controllers/Admin/OrderShipmentController';
import { useConfirm } from '@/components/admin/confirm';
import { LineItemsList, type LineItemRow } from '@/components/admin/line-items-list';
import { StatusBadge } from '@/components/admin/status-badge';
import { TrackingLink } from '@/components/admin/tracking-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { multiply } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import { mediaSmallThumb } from '@/lib/media';
import type { FulfillmentGroupItem } from '@/lib/order-item-groups';
import { getStatusLabel } from '@/lib/order-utils';
import { Permission } from '@/lib/permissions';
import { getTranslation } from '@/lib/utils';
import type { Order, OrderShipment } from '@/types';

import { TrackingDialog } from './tracking-dialog';

interface FulfilledCardProps {
    order: Order;
    shipment: OrderShipment;
    items: FulfillmentGroupItem[];
}

export function FulfilledCard({ order, shipment, items }: FulfilledCardProps) {
    const { confirm } = useConfirm();
    const [editOpen, setEditOpen] = useState(false);

    const handleCancel = () => {
        confirm({
            title: __('Cancel fulfillment'),
            description: __('This will cancel the fulfillment and its items will become unfulfilled.'),
            variant: 'delete',
            confirmLabel: __('Cancel fulfillment'),
            action: () =>
                new Promise<void>((resolve) => {
                    router.delete(OrderShipmentController.destroy.url({ order: order.id, shipment: shipment.id }), {
                        preserveScroll: true,
                        onFinish: () => resolve(),
                    });
                }),
        });
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>
                        <StatusBadge status="fulfilled">{getStatusLabel('fulfilled')}</StatusBadge>
                    </CardTitle>

                    {order.shipping_carrier_name && (
                        <CardDescription>
                            <div className="flex items-center gap-2">
                                <TruckIcon className="size-4" />
                                <span>{getTranslation(order.shipping_carrier_name)}</span>
                            </div>
                        </CardDescription>
                    )}

                    <Can permission={Permission.OrdersFulfill}>
                        <CardAction>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" size="icon-sm">
                                        <EllipsisVerticalIcon />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={() => setEditOpen(true)}>
                                        {__('Edit tracking')}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem variant="destructive" onClick={handleCancel}>
                                        {__('Cancel fulfillment')}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </CardAction>
                    </Can>
                </CardHeader>

                <CardContent className="space-y-4">
                    <LineItemsList
                        currency={order.currency_code}
                        rows={items.map(({ orderItem, quantity, shippedRefunded }): LineItemRow => ({
                            key: orderItem.id,
                            thumbnail_url: mediaSmallThumb(orderItem.media),
                            media: orderItem.media,
                            title: getTranslation(orderItem.product_title),
                            variantTitle: orderItem.variant_title,
                            sku: orderItem.product_sku,
                            note: shippedRefunded ? (
                                <Badge variant="outline" className="w-fit text-amber-700 dark:text-amber-400">
                                    {shippedRefunded >= orderItem.quantity
                                        ? __('Refunded')
                                        : __(':count refunded', { count: shippedRefunded })}
                                </Badge>
                            ) : undefined,
                            quantity,
                            unitPrice: orderItem.unit_price,
                            totalPrice: multiply(orderItem.unit_price, quantity),
                        }))}
                    />

                    {shipment.tracking_number && <Separator />}

                    {shipment.tracking_number && (
                        <TrackingLink trackingNumber={shipment.tracking_number} trackingUrl={shipment.tracking_url} />
                    )}
                </CardContent>
            </Card>

            <TrackingDialog open={editOpen} onOpenChange={setEditOpen} shipment={shipment} />
        </>
    );
}
