import { Link } from '@inertiajs/react';
import { AlertTriangleIcon, PackagePlusIcon } from 'lucide-react';
import { memo, useState } from 'react';

import * as InventoryController from '@/actions/App/Http/Controllers/Admin/InventoryController';
import { HoverActions } from '@/components/admin/hover-actions';
import { StockAdjustmentDialog } from '@/components/admin/inventory/stock-adjustment-dialog';
import { Thumbnail } from '@/components/admin/thumbnail';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Can } from '@/components/ui/can';
import { TableCell, TableRow } from '@/components/ui/table';
import { useRowNavigation } from '@/hooks/admin/use-row-navigation';
import { __ } from '@/lib/i18n';
import { mediaAlt, mediaSmallThumb } from '@/lib/media';
import { Permission } from '@/lib/permissions';
import { cn, getTranslation } from '@/lib/utils';
import type { Product, ProductVariant } from '@/types';

interface InventoryRowProps {
    item: Product | ProductVariant;
    isVariant?: boolean;
}

export const InventoryRow = memo(({ item, isVariant = false }: InventoryRowProps) => {
    const [adjustmentDialogOpen, setAdjustmentDialogOpen] = useState(false);

    const product = item as Product;
    const variant = item as ProductVariant;

    const title = isVariant ? getTranslation(variant.title) : getTranslation(product.title);
    const stock = isVariant ? (variant.stock ?? 0) : (product.total_stock ?? 0);
    const hasVariants = !isVariant && (product.variants?.length ?? 0) > 0;
    const href = isVariant
        ? InventoryController.show(variant.product_id, { query: { variant: variant.id } })
        : InventoryController.show(product.id);

    const {
        canNavigate,
        handleRowClick: baseHandleRowClick,
        handleLinkClick,
    } = useRowNavigation({
        url: href.url,
        permission: Permission.InventoryView,
    });

    const handleRowClick = (e: React.MouseEvent) => {
        if (hasVariants) return;
        baseHandleRowClick(e);
    };

    const handleAdjustStock = (e: React.MouseEvent) => {
        e.stopPropagation();
        setAdjustmentDialogOpen(true);
    };

    return (
        <>
            <TableRow
                key={item.id}
                className={cn('group/item', !hasVariants && canNavigate && 'cursor-pointer')}
                onClick={handleRowClick}
            >
                <TableCell className={cn('flex items-center', isVariant && 'ps-12')}>
                    <Thumbnail
                        src={mediaSmallThumb(isVariant ? variant.media : product.featured_media)}
                        alt={mediaAlt(isVariant ? variant.media : product.featured_media, title)}
                        className="me-3"
                    />
                    <div className="flex items-center gap-2">
                        {hasVariants ? (
                            <span className="font-medium">{title}</span>
                        ) : (
                            <Link
                                href={href}
                                onClick={handleLinkClick}
                                className="font-medium underline-offset-4 hover:underline"
                                prefetch
                            >
                                {title}
                            </Link>
                        )}

                        {item.in_stock && item.is_low_stock && (
                            <Badge variant="destructive" className="gap-1">
                                <AlertTriangleIcon />
                                {__('Low stock')}
                            </Badge>
                        )}

                        {!item.in_stock && (
                            <Badge variant="destructive" className="gap-1">
                                <AlertTriangleIcon />
                                {__('Out of stock')}
                            </Badge>
                        )}
                    </div>
                </TableCell>
                <TableCell>{item.sku ?? '—'}</TableCell>
                <TableCell>
                    <div className={cn({ 'text-destructive': stock === 0 })}>
                        {__(':count in stock', { count: stock })}
                    </div>
                </TableCell>
                <TableCell>
                    {!hasVariants && (
                        <Can permission={Permission.InventoryManage}>
                            <HoverActions>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={handleAdjustStock}
                                    title={__('Adjust stock')}
                                >
                                    <PackagePlusIcon />
                                </Button>
                            </HoverActions>
                        </Can>
                    )}
                </TableCell>
            </TableRow>

            {adjustmentDialogOpen && (
                <StockAdjustmentDialog
                    open={adjustmentDialogOpen}
                    onOpenChange={setAdjustmentDialogOpen}
                    product={product}
                    variant={isVariant ? variant : null}
                />
            )}
        </>
    );
});
InventoryRow.displayName = 'InventoryRow';
