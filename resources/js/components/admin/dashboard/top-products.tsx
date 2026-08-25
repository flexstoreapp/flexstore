import { Link } from '@inertiajs/react';

import * as ProductController from '@/actions/App/Http/Controllers/Admin/ProductController';
import { SectionHeading } from '@/components/admin/section-heading';
import { Thumbnail, ThumbnailRatio } from '@/components/admin/thumbnail';
import { Can } from '@/components/ui/can';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useRowNavigation } from '@/hooks/admin/use-row-navigation';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { mediaAlt, mediaSmallThumb } from '@/lib/media';
import { Permission } from '@/lib/permissions';
import { cn, getTranslation } from '@/lib/utils';
import type { TopProduct } from '@/types';

export function TopProducts({ products }: { products: TopProduct[] }) {
    const { formatMoney } = useFormatMoney();

    const { canNavigate, handleRowClick, handleLinkClick } = useRowNavigation<TopProduct>({
        url: (product) => ProductController.edit(product).url,
        permission: Permission.ProductsManage,
    });

    return (
        <div className="w-full min-w-0 space-y-4 overflow-hidden lg:col-span-3">
            <SectionHeading>{__('Top products')}</SectionHeading>
            <ScrollArea className="rounded-xl border shadow-xs">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{__('Product')}</TableHead>
                            <TableHead className="text-end">{__('Revenue')}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {products.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={2} className="py-8 text-center text-muted-foreground">
                                    {__('No sales data yet.')}
                                </TableCell>
                            </TableRow>
                        ) : (
                            <ThumbnailRatio media={products.map((product) => product.featured_media)}>
                                {products.map((product) => (
                                    <TableRow
                                        key={product.id}
                                        className={cn(canNavigate && 'cursor-pointer')}
                                        onClick={(e) => handleRowClick(e, product)}
                                    >
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <Thumbnail
                                                    src={mediaSmallThumb(product.featured_media)}
                                                    alt={mediaAlt(
                                                        product.featured_media,
                                                        getTranslation(product.title),
                                                    )}
                                                    className="h-9"
                                                />
                                                <div className="flex min-w-0 flex-col gap-0.5">
                                                    <Can
                                                        permission={Permission.ProductsManage}
                                                        fallback={
                                                            <span className="text-sm">
                                                                {getTranslation(product.title)}
                                                            </span>
                                                        }
                                                    >
                                                        <Link
                                                            href={ProductController.edit(product)}
                                                            onClick={handleLinkClick}
                                                            className="text-sm underline-offset-4 hover:underline"
                                                            prefetch
                                                        >
                                                            {getTranslation(product.title)}
                                                        </Link>
                                                    </Can>
                                                    <p className="text-xs text-muted-foreground">
                                                        {__(':count sold', { count: product.total_sold })}
                                                    </p>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-end">{formatMoney(product.revenue)}</TableCell>
                                    </TableRow>
                                ))}
                            </ThumbnailRatio>
                        )}
                    </TableBody>
                </Table>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>
        </div>
    );
}
