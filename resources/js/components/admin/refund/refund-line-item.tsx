import { useFormatMoney } from '@/hooks/use-format-money';

interface RefundLineItemProps {
    label: string;
    amount: string;
    currencyCode: string;
}

export function RefundLineItem({ label, amount, currencyCode }: RefundLineItemProps) {
    const { formatMoney } = useFormatMoney();

    return (
        <div className="flex justify-between gap-2">
            <span>{label}</span>
            <span dir="ltr">{formatMoney(amount, currencyCode)}</span>
        </div>
    );
}
