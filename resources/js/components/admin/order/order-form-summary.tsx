import { TaxBreakdown } from '@/components/admin/order/tax-breakdown';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { useFormatMoney } from '@/hooks/use-format-money';
import { isPositive } from '@/lib/decimal';
import { __ } from '@/lib/i18n';
import type { TaxDetail } from '@/types/tax';

interface OrderFormSummaryProps {
    calculating: boolean;
    subtotal: string;
    tax: string;
    taxDetails: TaxDetail[];
    shipping: string;
    showShipping?: boolean;
    discount: string;
    total: string;
    currencyCode?: string;
}

export function OrderFormSummary({
    calculating,
    subtotal,
    tax,
    taxDetails,
    shipping,
    showShipping = true,
    discount,
    total,
    currencyCode,
}: OrderFormSummaryProps) {
    const { formatMoney } = useFormatMoney();

    return (
        <Card className="relative">
            {calculating && (
                <div className="absolute inset-0 z-50 flex items-center justify-center rounded-xl backdrop-blur-xs">
                    <Spinner className="size-6" />
                </div>
            )}
            <CardHeader>
                <CardTitle>{__('Order summary')}</CardTitle>
                <CardDescription>{__('Review order totals before submitting')}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
                <div className="flex justify-between gap-2">
                    <span>{__('Subtotal')}</span>
                    <span>{formatMoney(subtotal, currencyCode)}</span>
                </div>

                {isPositive(discount || '0') && (
                    <div className="flex justify-between gap-2">
                        <span>{__('Discount')}</span>
                        <span dir="ltr">-{formatMoney(discount, currencyCode)}</span>
                    </div>
                )}

                {showShipping && isPositive(shipping || '0') && (
                    <div className="flex justify-between gap-2">
                        <span>{__('Shipping')}</span>
                        <span>{formatMoney(shipping, currencyCode)}</span>
                    </div>
                )}

                {isPositive(tax || '0') && (
                    <TaxBreakdown taxTotal={tax} taxDetails={taxDetails} currencyCode={currencyCode} />
                )}

                <Separator />

                <div className="flex justify-between gap-2 font-semibold">
                    <span>{__('Total')}</span>
                    <span>{formatMoney(total, currencyCode)}</span>
                </div>
            </CardContent>
        </Card>
    );
}
