import { PackageIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { Thumbnail } from '@/components/admin/thumbnail';
import { Badge } from '@/components/ui/badge';
import { useFormatMoney } from '@/hooks/use-format-money';
import { cn } from '@/lib/utils';

interface OrderLineItemProps {
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
    className?: string;
}

export function OrderLineItem({
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
    className,
}: OrderLineItemProps) {
    const { formatMoney } = useFormatMoney();

    return (
        <div className={cn('flex items-center gap-4 py-4 first:pt-0 last:pb-0', className)}>
            <Thumbnail
                src={thumbnail_url}
                alt={title}
                className="h-12 rounded-lg bg-muted/40"
                fallback={<PackageIcon className="size-6" strokeWidth={1.5} />}
            />

            <div className="min-w-0 flex-1 space-y-1">
                <p className="text-sm font-medium">{title}</p>
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

            <div className="flex shrink-0 items-center gap-4 text-sm">
                {unitPrice !== undefined && <span>{formatMoney(unitPrice, currencyCode)}</span>}
                {quantityControl ??
                    (quantity !== undefined && (
                        <span className="flex items-center gap-2 text-muted-foreground">
                            &times;
                            <Badge variant="secondary" className="font-normal">
                                {quantity}
                            </Badge>
                        </span>
                    ))}
                {totalPrice !== undefined && (
                    <span className="min-w-16 text-end font-medium">{formatMoney(totalPrice, currencyCode)}</span>
                )}
                {trailing}
            </div>
        </div>
    );
}
