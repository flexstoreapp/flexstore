import { QuantityStepper } from '@/components/storefront/quantity-stepper';
import { __ } from '@/lib/i18n';

interface BuyBoxQuantityProps {
    value: number;
    onChange: (value: number) => void;
    max?: number;
}

export function BuyBoxQuantity({ value, onChange, max }: BuyBoxQuantityProps) {
    return (
        <div className="mt-5">
            <span className="block text-sm font-semibold tracking-label text-ink uppercase">{__('Quantity')}</span>
            <div className="mt-3">
                <QuantityStepper value={value} onChange={onChange} max={max} ariaLabel={__('Quantity')} />
            </div>
        </div>
    );
}
