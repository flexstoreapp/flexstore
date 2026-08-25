import { MinusIcon, PlusIcon } from 'lucide-react';

import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface QuantityStepperProps {
    value: number;
    onChange: (value: number) => void;
    min?: number;
    max?: number;
    disabled?: boolean;
    size?: 'sm' | 'md';
    ariaLabel?: string;
}

const sizeStyles = {
    sm: { frame: 'h-8 rounded-sm', button: 'w-8', count: 'w-9', start: 'rounded-s-sm', end: 'rounded-e-sm', icon: 13 },
    md: {
        frame: 'h-10 rounded-md',
        button: 'w-10',
        count: 'w-11 text-lg',
        start: 'rounded-s-md',
        end: 'rounded-e-md',
        icon: 16,
    },
} as const;

const buttonClass =
    'text-muted can-hover:hover:bg-surface-2 disabled:text-line-strong grid place-items-center transition disabled:pointer-events-none';

export function QuantityStepper({
    value,
    onChange,
    min = 1,
    max,
    disabled = false,
    size = 'md',
    ariaLabel,
}: QuantityStepperProps) {
    const styles = sizeStyles[size];
    const canDecrease = !disabled && value > min;
    const canIncrease = !disabled && (max === undefined || value < max);

    return (
        <div
            role="group"
            dir="ltr"
            aria-label={ariaLabel}
            className={cn(
                'inline-flex items-stretch overflow-hidden border border-line-strong bg-surface',
                styles.frame,
            )}
        >
            <button
                type="button"
                aria-label={__('Decrease quantity')}
                disabled={!canDecrease}
                onClick={() => onChange(value - 1)}
                className={cn(buttonClass, styles.button, styles.start)}
            >
                <MinusIcon size={styles.icon} aria-hidden="true" />
            </button>
            <span
                aria-live="polite"
                aria-label={__('Quantity: :count', { count: value })}
                className={cn(
                    'grid place-items-center border-x border-line font-semibold tabular-nums select-none',
                    styles.count,
                )}
            >
                {value}
            </span>
            <button
                type="button"
                aria-label={__('Increase quantity')}
                disabled={!canIncrease}
                onClick={() => onChange(value + 1)}
                className={cn(buttonClass, styles.button, styles.end)}
            >
                <PlusIcon size={styles.icon} aria-hidden="true" />
            </button>
        </div>
    );
}
