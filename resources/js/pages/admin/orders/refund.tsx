import { router, usePage } from '@inertiajs/react';
import { useCallback, useMemo, useState } from 'react';

import * as OrderController from '@/actions/App/Http/Controllers/Admin/OrderController';
import * as OrderRefundController from '@/actions/App/Http/Controllers/Admin/OrderRefundController';
import { useConfirm } from '@/components/admin/confirm';
import { Heading } from '@/components/admin/heading';
import { RefundItems } from '@/components/admin/refund/refund-items';
import { RefundReason } from '@/components/admin/refund/refund-reason';
import { RefundSummary } from '@/components/admin/refund/refund-summary';
import { type RefundItemState, useRefundCalculations } from '@/hooks/admin/use-refund-calculations';
import { useFormatId } from '@/hooks/use-format-id';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useUnsavedChangesAlert } from '@/hooks/use-unsaved-changes-alert';
import { divide, isPositive, multiply, sum } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import type { Order } from '@/types';

interface RefundProps {
    order: Order;
    refundableQuantities: Record<number, number>;
    refundableShippingAmount: string;
    maxRefundableAmount: string;
    supportsGatewayRefund: boolean;
}

export default function Refund({
    order,
    refundableQuantities,
    refundableShippingAmount,
    maxRefundableAmount,
    supportsGatewayRefund,
}: RefundProps) {
    const { errors } = usePage().props;
    const [itemStates, setItemStates] = useState<Record<number, RefundItemState>>({});
    const [restock, setRestock] = useState(true);
    const [notifyCustomer, setNotifyCustomer] = useState(true);
    const [shippingAmount, setShippingAmount] = useState('0');
    const [manualRestockingFee, setManualRestockingFee] = useState<string | null>(null);
    const [manualTotal, setManualTotal] = useState<string | null>(null);

    const restockingFeePercent = 0;
    const defaultRestockingFee = useMemo(() => {
        if (!restockingFeePercent) return '0';

        const subtotal = sum(
            Object.values(itemStates)
                .filter((state) => state.quantity > 0)
                .map((state) => {
                    const item = order.items?.find((orderItem) => orderItem.id === state.order_item_id);
                    return item ? multiply(item.unit_price, String(state.quantity)) : '0';
                }),
        );

        return isPositive(subtotal) ? divide(multiply(subtotal, String(restockingFeePercent)), '100') : '0';
    }, [restockingFeePercent, itemStates, order.items]);
    const restockingFee = manualRestockingFee ?? defaultRestockingFee;
    const [reason, setReason] = useState('');
    const [refundMethod, setRefundMethod] = useState(supportsGatewayRefund ? 'process_through_gateway' : 'record_only');

    const isDirty =
        Object.values(itemStates).some((s) => s.quantity > 0) ||
        isPositive(shippingAmount) ||
        manualRestockingFee !== null ||
        manualTotal !== null ||
        reason !== '';
    const { confirm } = useConfirm();
    useUnsavedChangesAlert(isDirty, { confirm });

    const { formatMoney } = useFormatMoney();
    const formatId = useFormatId();
    const hasRefundableItems = Object.values(refundableQuantities).some((qty) => qty > 0);

    const { totalRefundAmount, exceedsMax, discountTotal, taxTotal, itemAmounts } = useRefundCalculations({
        order,
        itemStates,
        shippingAmount,
        restockingFee,
        manualTotal,
        maxRefundableAmount,
    });

    const handleQuantityChange = useCallback(
        (itemId: number, delta: number) => {
            setItemStates((prev) => {
                const current = prev[itemId];
                const newQuantity = (current?.quantity ?? 0) + delta;
                const item = order.items?.find((i) => i.id === itemId);

                if (!item || newQuantity < 0) return prev;

                return {
                    ...prev,
                    [itemId]: {
                        order_item_id: itemId,
                        quantity: newQuantity,
                    },
                };
            });
        },
        [order.items],
    );

    const handleTotalChange = (value: string) => {
        setManualTotal(value);
    };

    const handleTotalReset = () => {
        setManualTotal(null);
    };

    const handleSubmit = (e: React.SubmitEvent) => {
        e.preventDefault();

        if (exceedsMax) return;

        confirm({
            title: __('Process refund?'),
            description: __('This will refund :amount for order :id.', {
                amount: formatMoney(totalRefundAmount, order.currency_code),
                id: formatId(order.id),
            }),
            action: () => {
                const items = Object.values(itemStates)
                    .filter((state) => state.quantity > 0)
                    .map((state) => ({
                        order_item_id: state.order_item_id,
                        quantity: state.quantity,
                    }));

                return new Promise<void>((resolve) => {
                    router.post(
                        OrderRefundController.store(order.id),
                        {
                            reason,
                            restock,
                            notify_customer: notifyCustomer,
                            refund_method: refundMethod,
                            items,
                            ...(isPositive(shippingAmount) && { shipping_amount: shippingAmount }),
                            ...(isPositive(restockingFee) && { restocking_fee: restockingFee }),
                            ...(manualTotal !== null && { total: totalRefundAmount, is_manual_total: true }),
                        },
                        { preserveScroll: true, preserveState: true, onFinish: () => resolve() },
                    );
                });
            },
        });
    };

    const typedErrors = errors as Record<string, string>;
    const gatewayError = typedErrors.gateway ?? null;
    const validationErrors = Object.entries(typedErrors)
        .filter(([key]) => key !== 'gateway')
        .map(([, value]) => value)
        .filter(Boolean);
    const error = exceedsMax
        ? __('The total refund amount cannot exceed :amount.', {
              amount: formatMoney(maxRefundableAmount, order.currency_code),
          })
        : null;

    return (
        <>
            <Heading
                pageTitle={`${__('Refund')} - ${formatId(order.id)}`}
                title={__('Refund')}
                description={__('Process a refund for order :id', { id: formatId(order.id) })}
                backHref={OrderController.show(order.id)}
            />

            <form onSubmit={handleSubmit} className="mb-8">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        {hasRefundableItems && (
                            <RefundItems
                                items={order.items ?? []}
                                itemStates={itemStates}
                                itemAmounts={itemAmounts}
                                refundableQuantities={refundableQuantities}
                                restock={restock}
                                showRestock
                                currencyCode={order.currency_code}
                                onQuantityChange={handleQuantityChange}
                                onRestockChange={setRestock}
                            />
                        )}
                        <RefundReason reason={reason} onReasonChange={setReason} />
                    </div>

                    <div className="space-y-6">
                        <RefundSummary
                            items={order.items!}
                            itemStates={itemStates}
                            itemAmounts={itemAmounts}
                            refundableShippingAmount={refundableShippingAmount}
                            shippingAmount={shippingAmount}
                            restockingFee={restockingFee}
                            restockingFeePercent={restockingFeePercent}
                            isRestockingFeeOverridden={manualRestockingFee !== null}
                            showRestockingFee={false}
                            discountTotal={discountTotal}
                            taxTotal={taxTotal}
                            totalRefundAmount={totalRefundAmount}
                            isTotalOverridden={manualTotal !== null}
                            maxRefundableAmount={maxRefundableAmount}
                            currencyCode={order.currency_code}
                            exchangeRate={order.exchange_rate}
                            refundMethod={refundMethod}
                            supportsGatewayRefund={supportsGatewayRefund}
                            notifyCustomer={notifyCustomer}
                            submitDisabled={!isPositive(totalRefundAmount) || exceedsMax}
                            error={error}
                            gatewayError={gatewayError}
                            validationErrors={validationErrors}
                            onShippingAmountChange={setShippingAmount}
                            onRestockingFeeChange={setManualRestockingFee}
                            onRestockingFeeReset={() => setManualRestockingFee(null)}
                            onTotalChange={handleTotalChange}
                            onTotalReset={handleTotalReset}
                            onRefundMethodChange={setRefundMethod}
                            onNotifyCustomerChange={setNotifyCustomer}
                        />
                    </div>
                </div>
            </form>
        </>
    );
}

Refund.layout = ({ order }: RefundProps) => ({
    breadcrumbs: [
        { title: __('Orders'), href: OrderController.index() },
        { title: `#${order.id}`, href: OrderController.show(order) },
        { title: __('Refund'), href: OrderRefundController.create(order) },
    ],
});
