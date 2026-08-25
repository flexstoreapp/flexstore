import { Separator } from '@/components/ui/separator';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { getTranslation, stripTrailingZeros } from '@/lib/utils';
import type { TaxDetail } from '@/types/tax';

interface TaxBreakdownProps {
    taxTotal: string;
    taxDetails: TaxDetail[];
    currencyCode?: string;
}

export function TaxBreakdown({ taxTotal, taxDetails, currencyCode }: TaxBreakdownProps) {
    const { formatMoney } = useFormatMoney();

    if (taxDetails.length === 0) {
        return (
            <div className="flex justify-between gap-2 text-sm">
                <span>{__('Tax')}</span>
                <span>{formatMoney(taxTotal, currencyCode)}</span>
            </div>
        );
    }

    return (
        <>
            <Separator />
            {taxDetails.map((detail, index) => (
                <div key={index} className="flex justify-between gap-2 text-sm">
                    <div className="flex flex-col">
                        <span>{getTranslation(detail.tax_name)}</span>
                        <span className="text-xs text-muted-foreground">
                            {__(':tax_rate% on :taxable_amount', {
                                tax_rate: stripTrailingZeros(detail.tax_rate),
                                taxable_amount: formatMoney(detail.taxable_amount, currencyCode),
                            })}
                        </span>
                    </div>
                    <span>{formatMoney(detail.tax_amount, currencyCode)}</span>
                </div>
            ))}
            {taxDetails.length > 1 && (
                <>
                    <Separator />
                    <div className="flex justify-between gap-2 text-sm">
                        <span>{__('Total tax')}</span>
                        <span>{formatMoney(taxTotal, currencyCode)}</span>
                    </div>
                </>
            )}
        </>
    );
}
