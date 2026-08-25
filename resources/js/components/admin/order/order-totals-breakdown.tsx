import { usePage } from '@inertiajs/react';

import { InclTaxLabel } from '@/components/admin/order/incl-tax-label';
import { useFormatMoney } from '@/hooks/use-format-money';
import { isPositive } from '@/lib/decimal';
import { __, transChoice } from '@/lib/i18n';
import { getTranslation, stripTrailingZeros } from '@/lib/utils';
import type { OrderTaxDetail, TranslatableField } from '@/types';

interface OrderTotalsBreakdownProps {
    subtotal: string;
    discountTotal: string;
    shippingTotal: string;
    taxTotal: string;
    total: string;
    currencyCode: string;
    exchangeRate: string;
    itemsCount: number;
    pricesIncludeTax?: boolean;
    couponCode?: string | null;
    shippingRateName?: TranslatableField | null;
    taxDetails?: OrderTaxDetail[];
}

export function OrderTotalsBreakdown({
    subtotal,
    discountTotal,
    shippingTotal,
    taxTotal,
    total,
    currencyCode,
    exchangeRate,
    itemsCount,
    pricesIncludeTax = false,
    couponCode,
    shippingRateName,
    taxDetails = [],
}: OrderTotalsBreakdownProps) {
    const { baseCurrency } = usePage().props;
    const { formatMoney, convertToBaseCurrency } = useFormatMoney();
    const isDifferentCurrency = currencyCode && currencyCode !== baseCurrency;

    return (
        <div className="space-y-2 text-sm">
            <div className="grid grid-cols-[auto_1fr_auto] gap-x-4">
                <span>
                    {__('Subtotal')} <InclTaxLabel show={pricesIncludeTax} />
                </span>
                <span className="text-muted-foreground">{transChoice(':count item|:count items', itemsCount)}</span>
                <span className="text-end">{formatMoney(subtotal, currencyCode)}</span>
            </div>

            {isPositive(discountTotal) && (
                <div className="grid grid-cols-[auto_1fr_auto] gap-x-4">
                    <span>{__('Discount')}</span>
                    <span className="text-muted-foreground">
                        {couponCode && __('Coupon: :code', { code: couponCode })}
                    </span>
                    <span dir="ltr" className="text-end">
                        -{formatMoney(discountTotal, currencyCode)}
                    </span>
                </div>
            )}

            {isPositive(shippingTotal) && (
                <div className="grid grid-cols-[auto_1fr_auto] gap-x-4">
                    <span>{__('Shipping')}</span>
                    <span className="text-muted-foreground">
                        {shippingRateName && getTranslation(shippingRateName)}
                    </span>
                    <span className="text-end">{formatMoney(shippingTotal, currencyCode)}</span>
                </div>
            )}

            {isPositive(taxTotal) && taxDetails.length > 0 ? (
                taxDetails.map((detail, index) => (
                    <div key={index} className="grid grid-cols-[auto_1fr_auto] gap-x-4">
                        <span>{getTranslation(detail.tax_name)}</span>
                        <span className="text-muted-foreground">
                            {__(':tax_rate% on :taxable_amount', {
                                tax_rate: stripTrailingZeros(detail.tax_rate),
                                taxable_amount: formatMoney(detail.taxable_amount, currencyCode),
                            })}
                        </span>
                        <span className="text-end">{formatMoney(detail.tax_amount, currencyCode)}</span>
                    </div>
                ))
            ) : isPositive(taxTotal) ? (
                <div className="grid grid-cols-[auto_1fr_auto] gap-x-4">
                    <span>{__('Tax')}</span>
                    <span />
                    <span className="text-end">{formatMoney(taxTotal, currencyCode)}</span>
                </div>
            ) : null}

            <div className="mt-1 grid grid-cols-[auto_1fr_auto] items-center gap-x-4 border-t pt-3 font-semibold">
                <span>{__('Total')}</span>
                <span className="text-sm font-normal text-muted-foreground">
                    {isDifferentCurrency &&
                        `≈ ${formatMoney(convertToBaseCurrency(total, exchangeRate), baseCurrency)}`}
                </span>
                <span className="text-end">{formatMoney(total, currencyCode)}</span>
            </div>
        </div>
    );
}
