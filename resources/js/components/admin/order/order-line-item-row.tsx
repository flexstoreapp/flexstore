import { PackageIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { Thumbnail } from '@/components/admin/thumbnail';
import { Badge } from '@/components/ui/badge';
import { TableCell, TableRow } from '@/components/ui/table';
import { useFormatMoney } from '@/hooks/use-format-money';
import { cn } from '@/lib/utils';

interface OrderLineItemRowProps {
    thumbnail_url?: string | null;
    title: string;
    variantTitle?: string | null;
    sku?: string | null;
    note?: ReactNode;
    quantity?: number;
    quantityControl?: ReactNode;
    unitPrice?: string;
    totalPrice?: string;
    currencyCode?: string;
    trailing?: ReactNode;
    children?: ReactNode;
    className?: string;
}

export function OrderLineItemRow({
    thumbnail_url,
    title,
    variantTitle,
    sku,
    note,
    quantity,
    quantityControl,
    unitPrice,
    totalPrice,
    currencyCode,
    trailing,
    children,
    className,
}: OrderLineItemRowProps) {
    const { formatMoney } = useFormatMoney();

    return (
        <TableRow className={cn('group/item', className)}>
            <TableCell className="min-w-64 whitespace-normal">
                {children}
                <div className="flex items-center gap-3">
                    <Thumbnail
                        src={thumbnail_url}
                        alt={title}
                        className="h-12 rounded-lg bg-muted/40"
                        fallback={<PackageIcon className="size-6" strokeWidth={1.5} />}
                    />

                    <div className="min-w-0 flex-1 space-y-1">
                        <p className="line-clamp-2 text-sm font-medium">{title}</p>
                        {(variantTitle || sku) && (
                            <div className="flex flex-wrap items-center gap-2">
                                {variantTitle && (
                                    <Badge variant="secondary" className="font-normal">
                                        {variantTitle}
                                    </Badge>
                                )}
                                {sku && <span className="text-sm text-muted-foreground">{sku}</span>}
                            </div>
                        )}
                        {note && <div>{note}</div>}
                    </div>
                </div>
            </TableCell>

            <TableCell className="text-end">
                {unitPrice !== undefined && formatMoney(unitPrice, currencyCode)}
            </TableCell>

            <TableCell className="text-end">
                {quantityControl ? (
                    <div className="flex justify-end">{quantityControl}</div>
                ) : (
                    quantity !== undefined && quantity
                )}
            </TableCell>

            <TableCell className="text-end font-medium">
                {totalPrice !== undefined && formatMoney(totalPrice, currencyCode)}
            </TableCell>

            {trailing !== undefined && <TableCell className="w-10">{trailing}</TableCell>}
        </TableRow>
    );
}
