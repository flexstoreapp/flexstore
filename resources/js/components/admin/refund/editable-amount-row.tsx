import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFormatMoney } from '@/hooks/use-format-money';
import { useCurrencyDecimalPlaces, useMoneyInputProps } from '@/hooks/use-money-input';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface EditableAmountRowProps {
    id: string;
    label: string;
    value: string;
    max: string;
    bold?: boolean;
    showMaxAction?: boolean;
    showResetAction?: boolean;
    currencyCode: string;
    onMaxClick: () => void;
    onResetClick?: () => void;
    onChange: (value: string) => void;
}

export function EditableAmountRow({
    id,
    label,
    value,
    max,
    bold,
    showMaxAction = true,
    showResetAction,
    currencyCode,
    onMaxClick,
    onResetClick,
    onChange,
}: EditableAmountRowProps) {
    const { formatMoney } = useFormatMoney();
    const moneyInputProps = useMoneyInputProps(currencyCode);
    const decimalPlaces = useCurrencyDecimalPlaces(currencyCode);

    const normalizeAmount = (v: string) => {
        const num = parseFloat(v);
        return isNaN(num) ? v : num.toFixed(decimalPlaces);
    };

    const normalizedMax = normalizeAmount(max);
    const displayValue = normalizeAmount(value);

    return (
        <div className="space-y-1">
            <div className="flex items-center justify-between gap-2">
                <Label htmlFor={id} className={cn('font-normal', bold && 'font-semibold')}>
                    {label}
                </Label>
                <Input
                    {...moneyInputProps}
                    id={id}
                    max={normalizedMax}
                    value={displayValue}
                    onChange={(e) => onChange(e.target.value)}
                    className={cn('h-8 w-28 text-end text-sm', bold && 'font-semibold')}
                />
            </div>
            {(showMaxAction || showResetAction) && (
                <div className="flex items-center justify-between gap-2 text-xs text-muted-foreground">
                    {showMaxAction ? (
                        <button type="button" onClick={onMaxClick} className="hover:text-foreground">
                            {__('Max: :amount', { amount: formatMoney(normalizedMax, currencyCode) })}
                        </button>
                    ) : (
                        <span />
                    )}
                    {showResetAction && (
                        <button type="button" onClick={onResetClick} className="hover:text-foreground">
                            {__('Reset')}
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
