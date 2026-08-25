import { OrderLineItem } from '@/components/admin/order/order-line-item';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { QuantityInput } from '@/components/ui/quantity-input';
import { Separator } from '@/components/ui/separator';
import { type RefundItemState } from '@/hooks/admin/use-refund-calculations';
import { transChoice } from '@/lib/i18n';
import { mediaSmallThumb } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { OrderItem } from '@/types';

interface RefundItemsProps {
    items: OrderItem[];
    itemStates: Record<number, RefundItemState>;
    itemAmounts: Record<number, string>;
    refundableQuantities: Record<number, number>;
    restock: boolean;
    showRestock?: boolean;
    currencyCode: string;
    onQuantityChange: (itemId: number, delta: number) => void;
    onRestockChange: (value: boolean) => void;
}

export function RefundItems({
    items,
    itemStates,
    itemAmounts,
    refundableQuantities,
    restock,
    showRestock = true,
    currencyCode,
    onQuantityChange,
    onRestockChange,
}: RefundItemsProps) {
    const refundableItems = items.filter((item) => (refundableQuantities[item.id] ?? 0) > 0);
    const totalQuantity = Object.values(itemStates).reduce((sum, state) => sum + state.quantity, 0);

    return (
        <Card>
            <CardContent className="space-y-4">
                <div className="divide-y">
                    {refundableItems.map((item) => {
                        const state = itemStates[item.id];
                        const quantity = state?.quantity ?? 0;
                        const maxQuantity = refundableQuantities[item.id] ?? 0;
                        const amount = itemAmounts[item.id] ?? '0.0000';

                        return (
                            <OrderLineItem
                                key={item.id}
                                thumbnail_url={mediaSmallThumb(item.media)}
                                title={getTranslation(item.product_title)}
                                variantTitle={item.variant_title}
                                sku={item.product_sku}
                                unitPrice={item.unit_price}
                                totalPrice={amount}
                                currencyCode={currencyCode}
                                quantityControl={
                                    <QuantityInput
                                        itemId={item.id}
                                        quantity={quantity}
                                        maxQuantity={maxQuantity}
                                        onQuantityChange={onQuantityChange}
                                    />
                                }
                            />
                        );
                    })}
                </div>

                {showRestock && totalQuantity > 0 && (
                    <>
                        <Separator />
                        <Field orientation="horizontal">
                            <Checkbox
                                id="restock"
                                checked={restock}
                                onCheckedChange={(checked: boolean) => onRestockChange(checked)}
                            />
                            <FieldLabel htmlFor="restock">
                                {transChoice('Restock :count item|Restock :count items', totalQuantity)}
                            </FieldLabel>
                        </Field>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
