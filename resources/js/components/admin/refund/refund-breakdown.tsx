import { Separator } from '@/components/ui/separator';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import type { OrderRefund } from '@/types';

import { ProductItemLine } from './product-item-line';

export function RefundBreakdown({ refund, currencyCode }: { refund: OrderRefund; currencyCode: string }) {
    const productItems = refund.items.filter((item) => item.type === 'product');
    const shippingItem = refund.items.find((item) => item.type === 'shipping');
    const discountItem = refund.items.find((item) => item.type === 'discount');
    const taxItem = refund.items.find((item) => item.type === 'tax');
    const restockingFeeItem = refund.items.find((item) => item.type === 'restocking_fee');
    const adjustmentItem = refund.items.find((item) => item.type === 'adjustment');
    const { formatMoney } = useFormatMoney();

    return (
        <div className="space-y-2">
            {productItems.map((item) => (
                <ProductItemLine key={item.id} item={item} currencyCode={currencyCode} />
            ))}

            {discountItem && (
                <div className="flex justify-between gap-2">
                    <span>{__('Discount')}</span>
                    <span dir="ltr">-{formatMoney(discountItem.amount, currencyCode)}</span>
                </div>
            )}

            {shippingItem && (
                <div className="flex justify-between gap-2">
                    <span>{__('Shipping')}</span>
                    <span>{formatMoney(shippingItem.amount, currencyCode)}</span>
                </div>
            )}

            {taxItem && (
                <div className="flex justify-between gap-2">
                    <span>{__('Tax')}</span>
                    <span>{formatMoney(taxItem.amount, currencyCode)}</span>
                </div>
            )}

            {restockingFeeItem && (
                <div className="flex justify-between gap-2">
                    <span>{__('Restocking fee')}</span>
                    <span dir="ltr">-{formatMoney(restockingFeeItem.amount, currencyCode)}</span>
                </div>
            )}

            {adjustmentItem && (
                <div className="flex justify-between gap-2">
                    <span>{__('Manual adjustment')}</span>
                    <span dir="ltr">{formatMoney(adjustmentItem.amount, currencyCode)}</span>
                </div>
            )}

            <Separator />

            <div className="flex justify-between gap-2 font-medium">
                <span>
                    {__('Total')}
                    {refund.is_manual_total && (
                        <span className="ms-1 text-xs font-normal text-muted-foreground">({__('Manual')})</span>
                    )}
                </span>
                <span>{formatMoney(refund.amount, currencyCode)}</span>
            </div>
        </div>
    );
}
