import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';

interface OrderSummaryTotalProps {
    total: string;
    label?: string;
    currencyCode?: string;
}

export function OrderSummaryTotal({ total, label, currencyCode }: OrderSummaryTotalProps) {
    const { formatMoney } = useFormatMoney();

    return (
        <div className="mt-4 flex items-center justify-between border-t border-line pt-4">
            <span className="font-head text-lg font-bold text-ink">{label ?? __('Total')}</span>
            <span aria-live="polite" className="text-3xl font-bold text-ink">
                {formatMoney(total, currencyCode)}
            </span>
        </div>
    );
}
