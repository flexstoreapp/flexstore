import { usePage } from '@inertiajs/react';

import { AdaptiveSelect, type AdaptiveSelectOption } from '@/components/ui/adaptive-select';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertError } from '@/components/ui/alert-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { Separator } from '@/components/ui/separator';
import type { RefundItemState } from '@/hooks/admin/use-refund-calculations';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useCurrencyDecimalPlaces } from '@/hooks/use-money-input';
import { isPositive, sum } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import type { OrderItem } from '@/types';

import { EditableAmountRow } from './editable-amount-row';
import { RefundLineItem } from './refund-line-item';

interface RefundSummaryProps {
    items: OrderItem[];
    itemStates: Record<number, RefundItemState>;
    itemAmounts: Record<number, string>;
    refundableShippingAmount: string;
    shippingAmount: string;
    restockingFee: string;
    restockingFeePercent: number;
    isRestockingFeeOverridden: boolean;
    showRestockingFee: boolean;
    discountTotal: string;
    taxTotal: string;
    totalRefundAmount: string;
    isTotalOverridden: boolean;
    maxRefundableAmount: string;
    currencyCode: string;
    exchangeRate: string;
    refundMethod: string;
    supportsGatewayRefund: boolean;
    notifyCustomer: boolean;
    submitDisabled: boolean;
    error: string | null;
    gatewayError: string | null;
    validationErrors: string[];
    onShippingAmountChange: (value: string) => void;
    onRestockingFeeChange: (value: string) => void;
    onRestockingFeeReset: () => void;
    onTotalChange: (value: string) => void;
    onTotalReset: () => void;
    onRefundMethodChange: (value: string) => void;
    onNotifyCustomerChange: (value: boolean) => void;
}

export function RefundSummary({
    items,
    itemStates,
    itemAmounts,
    refundableShippingAmount,
    shippingAmount,
    restockingFee,
    restockingFeePercent,
    isRestockingFeeOverridden,
    showRestockingFee,
    discountTotal,
    taxTotal,
    totalRefundAmount,
    isTotalOverridden,
    maxRefundableAmount,
    currencyCode,
    exchangeRate,
    refundMethod,
    supportsGatewayRefund,
    notifyCustomer,
    submitDisabled,
    error,
    gatewayError,
    validationErrors,
    onShippingAmountChange,
    onRestockingFeeChange,
    onRestockingFeeReset,
    onTotalChange,
    onTotalReset,
    onRefundMethodChange,
    onNotifyCustomerChange,
}: RefundSummaryProps) {
    const { baseCurrency } = usePage().props;
    const { formatMoney, convertToBaseCurrency } = useFormatMoney();
    const decimalPlaces = useCurrencyDecimalPlaces(currencyCode);
    const normalizeAmount = (v: string) => Number(v).toFixed(decimalPlaces);
    const isDifferentCurrency = currencyCode && currencyCode !== baseCurrency;

    const selectedItems = items.filter((item) => {
        const state = itemStates[item.id];
        return state && state.quantity > 0;
    });

    const hasContent = selectedItems.length > 0;
    const isShippingChanged = isPositive(shippingAmount);

    const itemSubtotalQuantity = selectedItems.reduce((total, item) => total + itemStates[item.id].quantity, 0);
    const itemSubtotal = sum(selectedItems.map((item) => itemAmounts[item.id] ?? '0'));

    const refundMethodOptions: AdaptiveSelectOption[] = supportsGatewayRefund
        ? [
              { value: 'process_through_gateway', label: __('Process through gateway') },
              { value: 'record_only', label: __('Record only') },
          ]
        : [{ value: 'record_only', label: __('Record only') }];

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Refund summary')}</CardTitle>
                <CardDescription>{__('Review the refund breakdown before processing')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
                {hasContent && (
                    <RefundLineItem
                        label={__('Item subtotal (:count)', { count: itemSubtotalQuantity })}
                        amount={itemSubtotal}
                        currencyCode={currencyCode}
                    />
                )}

                {isPositive(discountTotal) && (
                    <RefundLineItem label={__('Discount')} amount={`-${discountTotal}`} currencyCode={currencyCode} />
                )}

                {isPositive(taxTotal) && (
                    <RefundLineItem label={__('Tax')} amount={taxTotal} currencyCode={currencyCode} />
                )}

                {isPositive(refundableShippingAmount) && (
                    <EditableAmountRow
                        id="refund-shipping"
                        label={__('Shipping')}
                        value={shippingAmount}
                        max={refundableShippingAmount}
                        currencyCode={currencyCode}
                        onChange={onShippingAmountChange}
                        onMaxClick={() => onShippingAmountChange(normalizeAmount(refundableShippingAmount))}
                        showResetAction={isShippingChanged}
                        onResetClick={() => onShippingAmountChange('0')}
                    />
                )}

                {hasContent && showRestockingFee && (
                    <EditableAmountRow
                        id="refund-restocking-fee"
                        label={
                            restockingFeePercent > 0
                                ? __('Restocking fee (:percent%)', { percent: restockingFeePercent })
                                : __('Restocking fee')
                        }
                        value={restockingFee}
                        max={itemSubtotal}
                        currencyCode={currencyCode}
                        onChange={onRestockingFeeChange}
                        onMaxClick={() => onRestockingFeeChange(normalizeAmount(itemSubtotal))}
                        showMaxAction={false}
                        showResetAction={isRestockingFeeOverridden}
                        onResetClick={onRestockingFeeReset}
                    />
                )}

                {hasContent && <Separator />}

                <EditableAmountRow
                    id="refund-total"
                    label={__('Total refund')}
                    value={totalRefundAmount}
                    max={maxRefundableAmount}
                    currencyCode={currencyCode}
                    onChange={onTotalChange}
                    onMaxClick={() => onTotalChange(normalizeAmount(maxRefundableAmount))}
                    showResetAction={isTotalOverridden}
                    onResetClick={onTotalReset}
                    bold
                />

                <Separator />

                <div className="space-y-6">
                    <Field>
                        <FieldLabel htmlFor="refund-method">{__('Refund method')}</FieldLabel>
                        <AdaptiveSelect
                            id="refund-method"
                            options={refundMethodOptions}
                            value={refundMethod}
                            onValueChange={onRefundMethodChange}
                            disabled={!supportsGatewayRefund}
                        />
                    </Field>

                    <Field orientation="horizontal">
                        <Checkbox
                            id="notify_customer"
                            checked={notifyCustomer}
                            onCheckedChange={(checked: boolean) => onNotifyCustomerChange(checked)}
                        />
                        <FieldLabel htmlFor="notify_customer">{__('Notify customer')}</FieldLabel>
                    </Field>

                    {gatewayError && (
                        <Alert variant="destructive">
                            <AlertTitle>{__('Gateway refund failed')}</AlertTitle>
                            <AlertDescription>{gatewayError}</AlertDescription>
                        </Alert>
                    )}

                    {error && (
                        <Alert variant="destructive">
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    {validationErrors.length > 0 && <AlertError errors={validationErrors} />}

                    <div className="space-y-2">
                        <Button type="submit" className="w-full" disabled={submitDisabled}>
                            {isPositive(totalRefundAmount)
                                ? __('Refund :amount', { amount: formatMoney(totalRefundAmount, currencyCode) })
                                : __('Refund')}
                        </Button>

                        {isDifferentCurrency && isPositive(totalRefundAmount) && (
                            <p className="text-center text-xs text-muted-foreground">
                                {__('Approximately :amount', {
                                    amount: formatMoney(
                                        convertToBaseCurrency(totalRefundAmount, exchangeRate),
                                        baseCurrency,
                                    ),
                                })}
                            </p>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
