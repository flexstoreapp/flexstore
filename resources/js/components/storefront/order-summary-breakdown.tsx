import { useFormatMoney } from '@/hooks/use-format-money';
import { isPositive, isZero } from '@/lib/decimal';
import { __, transChoice } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { DisplayTaxTotals, TaxDetail } from '@/types';

interface OrderSummaryBreakdownProps {
    subtotal: string;
    discountTotal: string;
    shippingTotal: string;
    taxTotal: string;
    itemCount: number;
    requiresShipping: boolean;
    shippingResolved: boolean;
    pendingShippingLabel?: string;
    pricesIncludeTax: boolean;
    displayTaxTotals: DisplayTaxTotals;
    taxDetails?: TaxDetail[] | null;
    currencyCode?: string;
    topBorder?: boolean;
}

export function OrderSummaryBreakdown({
    subtotal,
    discountTotal,
    shippingTotal,
    taxTotal,
    itemCount,
    requiresShipping,
    shippingResolved,
    pendingShippingLabel,
    pricesIncludeTax,
    displayTaxTotals,
    taxDetails,
    currencyCode,
    topBorder = true,
}: OrderSummaryBreakdownProps) {
    const { formatMoney: format } = useFormatMoney();
    const formatMoney = (amount: string) => format(amount, currencyCode);

    const hasDiscount = isPositive(discountTotal);
    const hasTax = isPositive(taxTotal);
    const showItemizedTax = displayTaxTotals === 'itemized' && taxDetails && taxDetails.length > 0;

    return (
        <dl className={cn('m-0 flex flex-col gap-3', topBorder ? 'mt-5 border-t border-line pt-5' : 'mt-4')}>
            <div className="flex items-center justify-between">
                <dt>{transChoice('Subtotal (:count item)|Subtotal (:count items)', itemCount)}</dt>
                <dd className="m-0 font-semibold text-ink">{formatMoney(subtotal)}</dd>
            </div>

            {hasDiscount && (
                <div className="flex items-center justify-between">
                    <dt>{__('Discount')}</dt>
                    <dd className="m-0 font-semibold text-success">
                        <span dir="ltr">−{formatMoney(discountTotal)}</span>
                    </dd>
                </div>
            )}

            {requiresShipping && shippingResolved && (
                <div className="flex items-center justify-between">
                    <dt>{__('Shipping')}</dt>
                    <dd className="m-0 font-semibold text-ink">
                        {isZero(shippingTotal) ? __('Free') : formatMoney(shippingTotal)}
                    </dd>
                </div>
            )}

            {requiresShipping && !shippingResolved && pendingShippingLabel && (
                <div className="flex items-center justify-between">
                    <dt>{__('Shipping')}</dt>
                    <dd className="m-0 font-semibold text-ink">
                        <span className="text-sm font-normal text-muted">{pendingShippingLabel}</span>
                    </dd>
                </div>
            )}

            {showItemizedTax
                ? [
                      ...taxDetails.map((detail, index) => (
                          <div key={index} className="flex items-center justify-between">
                              <dt>{getTranslation(detail.tax_name)}</dt>
                              <dd className="m-0 font-semibold text-ink">{formatMoney(detail.tax_amount)}</dd>
                          </div>
                      )),
                      <div key="tax-total" className="flex items-center justify-between">
                          <dt>{__('Total tax')}</dt>
                          <dd className="m-0 font-semibold text-ink">{formatMoney(taxTotal)}</dd>
                      </div>,
                  ]
                : hasTax && (
                      <div className="flex items-center justify-between">
                          <dt>
                              {__('Tax')}
                              {pricesIncludeTax && <span className="ms-1 text-sm">({__('included')})</span>}
                          </dt>
                          <dd className="m-0 font-semibold text-ink">{formatMoney(taxTotal)}</dd>
                      </div>
                  )}
        </dl>
    );
}
