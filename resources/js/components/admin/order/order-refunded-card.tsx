import { LineItemsList, type LineItemRow } from '@/components/admin/line-items-list';
import { StatusBadge } from '@/components/admin/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { multiply } from '@/lib/decimal';
import { mediaSmallThumb } from '@/lib/media';
import type { FulfillmentGroupItem } from '@/lib/order-item-groups';
import { getStatusLabel } from '@/lib/order-utils';
import { getTranslation } from '@/lib/utils';
import type { Order } from '@/types';

interface OrderRefundedCardProps {
    order: Order;
    items: FulfillmentGroupItem[];
}

export function OrderRefundedCard({ order, items }: OrderRefundedCardProps) {
    return (
        <Card className="gap-4">
            <CardHeader>
                <CardTitle>
                    <StatusBadge status="refunded">{getStatusLabel('refunded')}</StatusBadge>
                </CardTitle>
            </CardHeader>

            <CardContent>
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
    );
}
