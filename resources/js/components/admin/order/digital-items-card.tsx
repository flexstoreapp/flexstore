import { FileTypeIcon } from '@/components/admin/file-type-icon';
import { LineItemsList, type LineItemRow } from '@/components/admin/line-items-list';
import { StatusBadge } from '@/components/admin/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useFormatDate } from '@/hooks/use-format-date';
import { multiply } from '@/lib/decimal';
import { __, transChoice } from '@/lib/i18n';
import { mediaSmallThumb } from '@/lib/media';
import type { FulfillmentGroupItem } from '@/lib/order-item-groups';
import { getStatusLabel } from '@/lib/order-utils';
import { getTranslation } from '@/lib/utils';
import type { Order, PaymentStatus } from '@/types';

interface DigitalItemsCardProps {
    order: Order;
    items: FulfillmentGroupItem[];
}

const PAID_STATUSES = new Set<PaymentStatus>(['paid', 'partially_refunded']);

export function DigitalItemsCard({ order, items }: DigitalItemsCardProps) {
    const formatDate = useFormatDate();
    const delivered = PAID_STATUSES.has(order.payment_status);

    const itemIds = new Set(items.map(({ orderItem }) => orderItem.id));
    const downloads = (order.item_downloads ?? []).filter((download) => itemIds.has(download.order_item_id));

    return (
        <Card className="gap-4">
            <CardHeader>
                <CardTitle>
                    {delivered ? (
                        <StatusBadge status="fulfilled">{getStatusLabel('fulfilled')}</StatusBadge>
                    ) : (
                        <StatusBadge status="unpaid">{__('Awaiting payment')}</StatusBadge>
                    )}
                </CardTitle>
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

                {downloads.length > 0 && <Separator />}

                {downloads.length > 0 && (
                    <div className="space-y-4">
                        {downloads.map((download) => {
                            const usageLabel = transChoice(
                                ':count download used|:count downloads used',
                                download.download_count,
                            );
                            const lastLabel = download.last_downloaded_at
                                ? __('Last downloaded on :date', { date: formatDate(download.last_downloaded_at) })
                                : __('Not downloaded yet');

                            return (
                                <div key={download.id} className="flex items-center gap-3">
                                    <div className="flex size-10 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                        <FileTypeIcon
                                            mimeType={download.mime_type}
                                            filename={download.original_filename}
                                            className="size-5"
                                            strokeWidth={1.5}
                                        />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium">{download.name}</p>
                                        <div className="mt-0.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-muted-foreground">
                                            <span>{usageLabel}</span>
                                            <span aria-hidden>&middot;</span>
                                            <span>{lastLabel}</span>
                                        </div>
                                    </div>
                                    {!download.is_available && (
                                        <Badge variant="outline" className="shrink-0 text-muted-foreground">
                                            {__('Unavailable')}
                                        </Badge>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
