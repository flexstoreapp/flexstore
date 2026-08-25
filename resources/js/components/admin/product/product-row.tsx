import { Link } from '@inertiajs/react';
import { memo, useMemo } from 'react';

import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { StatusBadge } from '@/components/admin/status-badge';
import { Thumbnail } from '@/components/admin/thumbnail';
import { Can } from '@/components/ui/can';
import { Checkbox } from '@/components/ui/checkbox';
import { TableCell, TableRow } from '@/components/ui/table';
import { useFormatProductPrice } from '@/hooks/admin/use-format-product-price';
import { usePermissions } from '@/hooks/admin/use-permissions';
import { useRowNavigation } from '@/hooks/admin/use-row-navigation';
import { __ } from '@/lib/i18n';
import { mediaAlt, mediaSmallThumb } from '@/lib/media';
import { Permission } from '@/lib/permissions';
import { cn, getTranslation } from '@/lib/utils';
import type { Product } from '@/types';

interface ProductRowProps {
    product: Product;
    isSelected: boolean;
    onSelectProduct: (productId: number, shiftKey?: boolean) => void;
}

export const ProductRow = memo(({ product, isSelected, onSelectProduct }: ProductRowProps) => {
    const formatProductPrice = useFormatProductPrice();
    const { hasPermission } = usePermissions();
    const canUpdate = hasPermission(Permission.ProductsManage);
    const canDelete = hasPermission(Permission.ProductsDelete);
    const showCheckbox = canUpdate || canDelete;

    const { canNavigate, handleRowClick, handleLinkClick } = useRowNavigation({
        url: ProductController.edit(product).url,
        permission: Permission.ProductsManage,
    });

    const handleSelectProduct = (e: React.MouseEvent) => {
        e.stopPropagation();
        onSelectProduct(product.id, e.shiftKey);
    };

    const tableRowClass = useMemo(() => {
        if (!canNavigate) return 'cursor-default';
        return showCheckbox ? '[&>td:not(:first-child)]:cursor-pointer' : 'cursor-pointer';
    }, [canNavigate, showCheckbox]);

    return (
        <TableRow
            key={product.id}
            data-state={isSelected && 'selected'}
            className={tableRowClass}
            onClick={handleRowClick}
        >
            <Can permissions={[Permission.ProductsManage, Permission.ProductsDelete]}>
                <TableCell onClick={handleSelectProduct} className="w-10">
                    <Checkbox
                        checked={isSelected}
                        aria-label={__('Select :name', { name: getTranslation(product.title) })}
                    />
                </TableCell>
            </Can>

            <TableCell className="flex items-center">
                <Thumbnail
                    src={mediaSmallThumb(product.featured_media)}
                    alt={mediaAlt(product.featured_media, getTranslation(product.title))}
                    className="me-5"
                />

                <Can
                    permission={Permission.ProductsManage}
                    fallback={<span className="font-medium">{getTranslation(product.title)}</span>}
                >
                    <Link
                        href={ProductController.edit(product)}
                        onClick={handleLinkClick}
                        className="font-medium underline-offset-4 hover:underline"
                        prefetch
                    >
                        {getTranslation(product.title)}
                    </Link>
                </Can>
            </TableCell>
            <TableCell>{product.category ? getTranslation(product.category.name) : '—'}</TableCell>
            <TableCell className="text-end">
                {formatProductPrice({
                    price: product.price,
                    priceRange: product.price_range,
                    hasVariants: Boolean(product.variants?.length),
                })}
            </TableCell>
            <TableCell>
                {product.track_stock || product.variants?.some((variant) => variant.track_stock) ? (
                    <span className={cn({ 'text-destructive': product.total_stock === 0 })}>
                        {__(':count in stock', { count: product.total_stock ?? 0 })}
                    </span>
                ) : (
                    <span className="text-muted-foreground">{__('Inventory not tracked')}</span>
                )}
            </TableCell>
            <TableCell>
                <StatusBadge status={product.is_active ? 'active' : 'inactive'}>
                    {product.is_active ? __('Active') : __('Inactive')}
                </StatusBadge>
            </TableCell>
        </TableRow>
    );
});
ProductRow.displayName = 'ProductRow';
