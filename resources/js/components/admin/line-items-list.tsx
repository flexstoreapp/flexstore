import type { ReactNode } from 'react';

import { OrderLineItemRow } from '@/components/admin/order/order-line-item-row';
import { ThumbnailRatio } from '@/components/admin/thumbnail';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { __ } from '@/lib/i18n';
import type { MediaDimensions } from '@/types/media';

interface LineItemsTableProps {
    children: ReactNode;
    withActions?: boolean;
}

export function LineItemsTable({ children, withActions = false }: LineItemsTableProps) {
    return (
        <ScrollArea className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead className="min-w-64">{__('Product')}</TableHead>
                        <TableHead className="text-end">{__('Price')}</TableHead>
                        <TableHead className="text-end">{__('Quantity')}</TableHead>
                        <TableHead className="text-end">{__('Total')}</TableHead>
                        {withActions && <TableHead className="w-10" />}
                    </TableRow>
                </TableHeader>
                <TableBody>{children}</TableBody>
            </Table>

            <ScrollBar orientation="horizontal" />
        </ScrollArea>
    );
}

export interface LineItemRow {
    key: string | number;
    thumbnail_url: string | null;
    media?: MediaDimensions | null;
    title: string;
    variantTitle?: string | null;
    sku?: string | null;
    note?: ReactNode;
    quantity: number;
    unitPrice: string;
    totalPrice: string;
}

interface LineItemsListProps {
    rows: LineItemRow[];
    currency: string;
}

export function LineItemsList({ rows, currency }: LineItemsListProps) {
    return (
        <LineItemsTable>
            <ThumbnailRatio media={rows.map((row) => row.media)}>
                {rows.map((row) => (
                    <OrderLineItemRow
                        key={row.key}
                        thumbnail_url={row.thumbnail_url}
                        title={row.title}
                        variantTitle={row.variantTitle}
                        sku={row.sku}
                        note={row.note}
                        quantity={row.quantity}
                        unitPrice={row.unitPrice}
                        totalPrice={row.totalPrice}
                        currencyCode={currency}
                    />
                ))}
            </ThumbnailRatio>
        </LineItemsTable>
    );
}
