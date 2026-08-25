import { useFormatMoney } from '@/hooks/use-format-money';
import { getTranslation } from '@/lib/utils';
import type { OrderRefundItem } from '@/types';

export function ProductItemLine({ item, currencyCode }: { item: OrderRefundItem; currencyCode: string }) {
    const { formatMoney } = useFormatMoney();

    if (!item.order_item) return null;

    const name = getTranslation(item.order_item.product_title);
    const variant = item.order_item.variant_title;
    const label = variant ? `${name} (${variant})` : name;

    return (
        <div className="flex justify-between gap-2">
            <span>
                {label} <span className="text-muted-foreground">&times; {item.quantity}</span>
            </span>
            <span>{formatMoney(item.amount, currencyCode)}</span>
        </div>
    );
}
