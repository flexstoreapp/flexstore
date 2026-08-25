import * as InventoryController from '@/actions/App/Http/Controllers/Admin/InventoryController';
import { Heading } from '@/components/admin/heading';
import { Statistic } from '@/components/admin/statistic';
import { TablePagination } from '@/components/admin/table-pagination';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormatTime } from '@/hooks/admin/use-format-time';
import { useListManagement } from '@/hooks/admin/use-list-management';
import { useFormatDate } from '@/hooks/use-format-date';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { Paginated, Product, ProductVariant, StockMovement } from '@/types';

interface InventoryHistoryProps {
    product: Product;
    variant?: ProductVariant | null;
    stockMovements: Paginated<StockMovement>;
}

const reasonLabels: Record<StockMovement['reason'], string> = {
    manual: __('Manual adjustment'),
    received: __('Received'),
    damaged: __('Damaged'),
    lost: __('Lost'),
    return: __('Return'),
    inventory_count: __('Inventory count'),
    transfer: __('Transfer'),
    sale: __('Sale'),
    refund: __('Refund'),
    cancellation: __('Cancellation'),
    other: __('Other'),
};

export default function InventoryHistory({ product, variant, stockMovements }: InventoryHistoryProps) {
    const { changePage } = useListManagement({
        data: stockMovements.data,
        filters: {},
        fetchOnly: ['stockMovements'],
    });

    const formatDate = useFormatDate();
    const formatTime = useFormatTime();

    const title = variant
        ? getTranslation(variant.product?.title) + ' - ' + getTranslation(variant.title)
        : getTranslation(product.title);

    const target = variant ?? product;

    return (
        <>
            <Heading
                pageTitle={`${__('Stock movement history')} - ${title}`}
                title={title}
                description={__('View stock movement history')}
                backHref={InventoryController.index()}
            />

            <div className="grid max-w-3xl grid-cols-3 divide-x divide-border">
                <Statistic label={__('SKU')} value={<span dir="ltr">{target.sku ?? '—'}</span>} className="ps-0" />
                <Statistic label={__('Current stock')} value={target.stock ?? 0} />
                <Statistic
                    label={__('Status')}
                    value={
                        target.in_stock ? (
                            <span className="text-success">{__('In stock')}</span>
                        ) : (
                            <span className="text-destructive">{__('Out of stock')}</span>
                        )
                    }
                />
            </div>

            <ScrollArea className="rounded-xl border shadow-xs">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{__('Date')}</TableHead>
                            <TableHead>{__('User')}</TableHead>
                            <TableHead className="text-end">{__('Change')}</TableHead>
                            <TableHead className="text-end">{__('Stock')}</TableHead>
                            <TableHead>{__('Reason')}</TableHead>
                            <TableHead>{__('Notes')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {stockMovements.data.map((movement) => (
                            <TableRow key={movement.id}>
                                <TableCell>
                                    <div className="flex flex-col gap-0.5">
                                        <span>{formatDate(movement.created_at)}</span>
                                        <span className="text-muted-foreground">{formatTime(movement.created_at)}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    {movement.user ? (
                                        <div className="flex flex-col gap-0.5">
                                            <span>{movement.user.name}</span>
                                            <span className="text-muted-foreground">{movement.user.email}</span>
                                        </div>
                                    ) : (
                                        '—'
                                    )}
                                </TableCell>
                                <TableCell
                                    className={cn(
                                        'text-end',
                                        movement.quantity > 0
                                            ? 'text-emerald-700 dark:text-emerald-400'
                                            : 'text-destructive',
                                    )}
                                >
                                    {movement.quantity > 0 ? '+' : ''}
                                    {movement.quantity}
                                </TableCell>
                                <TableCell className="text-end">
                                    <div className="flex items-center justify-end gap-2">
                                        <span className="sr-only">{__('Previous stock')}</span>
                                        <s className="text-muted-foreground">{movement.quantity_before}</s>
                                        <span className="sr-only">{__('New stock')}</span>
                                        {movement.quantity_after}
                                    </div>
                                </TableCell>
                                <TableCell>{reasonLabels[movement.reason]}</TableCell>
                                <TableCell>{movement.notes ?? '—'}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>

                    <TableFooter>
                        <TableRow>
                            {stockMovements.data.length === 0 ? (
                                <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                    {__('No items found.')}
                                </TableCell>
                            ) : (
                                <TableCell colSpan={6}>
                                    <TablePagination
                                        from={stockMovements.from}
                                        to={stockMovements.to}
                                        total={stockMovements.total}
                                        currentPage={stockMovements.current_page}
                                        lastPage={stockMovements.last_page}
                                        onPageChange={changePage}
                                    />
                                </TableCell>
                            )}
                        </TableRow>
                    </TableFooter>
                </Table>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>
        </>
    );
}

InventoryHistory.layout = ({ product, variant }: InventoryHistoryProps) => {
    const title = variant
        ? getTranslation(variant.product?.title) + ' - ' + getTranslation(variant.title)
        : getTranslation(product.title);

    return {
        breadcrumbs: [
            { title: __('Inventory'), href: InventoryController.index() },
            { title, href: InventoryController.index() },
        ],
    };
};
