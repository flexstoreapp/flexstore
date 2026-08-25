import { useMemo } from 'react';

import { useCurrencyDecimalPlaces } from '@/hooks/use-money-input';
import { add, divide, gt, isPositive, multiply, subtract, sum } from '@/lib/decimal';
import type { Order } from '@/types';

export interface RefundItemState {
    order_item_id: number;
    quantity: number;
}

interface UseRefundCalculationsProps {
    order: Order;
    itemStates: Record<number, RefundItemState>;
    shippingAmount: string;
    restockingFee?: string;
    manualTotal: string | null;
    maxRefundableAmount: string;
}

export function useRefundCalculations({
    itemStates,
    shippingAmount,
    restockingFee = '0',
    manualTotal,
    maxRefundableAmount,
    order,
}: UseRefundCalculationsProps) {
    const decimalPlaces = useCurrencyDecimalPlaces(order.currency_code);
    const zero = '0.0000';

    const { itemAmounts, calculatedTotal, discountTotal, taxTotal } = useMemo(() => {
        const amounts: Record<number, string> = {};
        const productAmounts: string[] = [];
        const taxAmounts: string[] = [];
        const discountAmounts: string[] = [];

        for (const state of Object.values(itemStates)) {
            if (state.quantity <= 0) continue;

            const item = order.items?.find((i) => i.id === state.order_item_id);
            if (!item) continue;

            const itemAmount = multiply(item.unit_price, state.quantity);
            amounts[state.order_item_id] = itemAmount;
            productAmounts.push(itemAmount);

            if (item.tax_amount && item.quantity > 0) {
                taxAmounts.push(multiply(divide(item.tax_amount, item.quantity), state.quantity));
            }

            if (order.discount_total && isPositive(order.subtotal) && item.quantity > 0) {
                const itemProportion = divide(item.total_price, order.subtotal, 8);
                const itemDiscountTotal = multiply(order.discount_total, itemProportion);
                discountAmounts.push(multiply(divide(itemDiscountTotal, item.quantity), state.quantity));
            }
        }

        const productTotal = productAmounts.length > 0 ? sum(productAmounts) : zero;
        const discount = discountAmounts.length > 0 ? sum(discountAmounts) : zero;
        const tax = taxAmounts.length > 0 ? sum(taxAmounts) : zero;
        const total = subtract(
            add(subtract(productTotal, discount), add(tax, shippingAmount || '0')),
            restockingFee || '0',
        );

        return {
            itemAmounts: amounts,
            calculatedTotal: gt(total, 0) ? total : zero,
            discountTotal: discount,
            taxTotal: tax,
        };
    }, [itemStates, shippingAmount, restockingFee, order]);

    const totalRefundAmount = manualTotal ?? calculatedTotal;

    const exceedsMax = gt(totalRefundAmount, maxRefundableAmount, decimalPlaces);

    return {
        maxRefundableAmount,
        itemAmounts,
        calculatedTotal,
        totalRefundAmount,
        exceedsMax,
        discountTotal,
        taxTotal,
        zero,
    };
}
