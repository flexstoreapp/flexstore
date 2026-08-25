import { BanknoteIcon, CreditCardIcon, WalletIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { OrderPaymentActions } from '@/components/admin/order/order-payment-actions';
import { OrderTotalsBreakdown } from '@/components/admin/order/order-totals-breakdown';
import { StatusBadge } from '@/components/admin/status-badge';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useFormatMoney } from '@/hooks/use-format-money';
import { isPositive } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import { getStatusLabel } from '@/lib/order-utils';
import { getTranslation } from '@/lib/utils';
import type { Order } from '@/types';
import type { DisplayTaxTotals } from '@/types/setting';

const DEFAULT_PAYMENT_ICON = <CreditCardIcon className="size-4" />;

const PAYMENT_ICONS: Record<string, ReactNode> = {
    cod: <BanknoteIcon className="size-4" />,
    paypal: <WalletIcon className="size-4" />,
    mercadopago: <WalletIcon className="size-4" />,
};

function getPaymentIcon(order: Order): ReactNode {
    const driver = order.payment_gateway?.driver;

    return driver ? (PAYMENT_ICONS[driver] ?? DEFAULT_PAYMENT_ICON) : DEFAULT_PAYMENT_ICON;
}

interface OrderPaymentCardProps {
    order: Order;
    displayTaxTotals: DisplayTaxTotals;
    canRecordPayment?: boolean;
    canRefundCredit?: boolean;
}

export function OrderPaymentCard({
    order,
    displayTaxTotals,
    canRecordPayment,
    canRefundCredit,
}: OrderPaymentCardProps) {
    const { formatMoney } = useFormatMoney();
    const hasRefunds = isPositive(order.refund_total);
    const totalItems = order.items?.reduce((sum, item) => sum + item.quantity, 0) ?? 0;
    const taxDetails = displayTaxTotals === 'itemized' ? (order.tax_details ?? []) : [];
    const hasBalanceDue = isPositive(order.balance_due_total);
    const hasCreditDue = isPositive(order.credit_due_total);
    const showPaidBreakdown = hasRefunds || hasBalanceDue || hasCreditDue;
    const hasPaymentActions = canRecordPayment || order.is_voidable || canRefundCredit;
    const paymentIcon = getPaymentIcon(order);

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    <StatusBadge status={order.payment_status}>{getStatusLabel(order.payment_status)}</StatusBadge>
                </CardTitle>

                {order.payment_gateway_name && (
                    <CardDescription>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            {paymentIcon}
                            <span>{getTranslation(order.payment_gateway_name)}</span>
                        </div>
                    </CardDescription>
                )}

                {hasPaymentActions && (
                    <CardAction>
                        <OrderPaymentActions
                            order={order}
                            canRecordPayment={canRecordPayment}
                            canRefundCredit={canRefundCredit}
                        />
                    </CardAction>
                )}
            </CardHeader>

            <CardContent>
                <div className="space-y-2 text-sm">
                    <OrderTotalsBreakdown
                        subtotal={order.subtotal}
                        discountTotal={order.discount_total}
                        shippingTotal={order.shipping_total}
                        taxTotal={order.tax_total}
                        total={order.total}
                        currencyCode={order.currency_code}
                        exchangeRate={order.exchange_rate}
                        itemsCount={totalItems}
                        pricesIncludeTax={order.prices_include_tax}
                        couponCode={order.coupon_code}
                        shippingRateName={order.shipping_rate_name}
                        taxDetails={taxDetails}
                    />

                    {showPaidBreakdown && isPositive(order.paid_total) && (
                        <div className="grid grid-cols-[auto_1fr_auto] gap-x-4">
                            <span>{__('Paid')}</span>
                            <span />
                            <span className="text-end">{formatMoney(order.paid_total, order.currency_code)}</span>
                        </div>
                    )}

                    {hasRefunds && (
                        <div className="grid grid-cols-[auto_1fr_auto] gap-x-4">
                            <span>{__('Refunded')}</span>
                            <span />
                            <span dir="ltr" className="text-end">
                                -{formatMoney(order.refund_total, order.currency_code)}
                            </span>
                        </div>
                    )}

                    {hasRefunds && (
                        <div className="mt-1 grid grid-cols-[auto_1fr_auto] gap-x-4 border-t pt-3 font-semibold">
                            <span>{__('Net payment')}</span>
                            <span />
                            <span className="text-end">{formatMoney(order.net_paid_total, order.currency_code)}</span>
                        </div>
                    )}

                    {hasBalanceDue && (
                        <div className="grid grid-cols-[auto_1fr_auto] gap-x-4 text-amber-700 dark:text-amber-400">
                            <span className="font-medium">{__('Balance due')}</span>
                            <span />
                            <span className="text-end font-medium">
                                {formatMoney(order.balance_due_total, order.currency_code)}
                            </span>
                        </div>
                    )}

                    {hasCreditDue && (
                        <div className="grid grid-cols-[auto_1fr_auto] gap-x-4 text-red-700 dark:text-red-400">
                            <span className="font-medium">{__('Credit owed')}</span>
                            <span />
                            <span className="text-end font-medium">
                                {formatMoney(order.credit_due_total, order.currency_code)}
                            </span>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
