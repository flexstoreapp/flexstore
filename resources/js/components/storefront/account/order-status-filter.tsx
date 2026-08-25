import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const FILTERS: { value: string | null; label: string }[] = [
    { value: null, label: __('All') },
    { value: 'unfulfilled', label: __('Unfulfilled') },
    { value: 'in_progress', label: __('In progress') },
    { value: 'fulfilled', label: __('Fulfilled') },
    { value: 'on_hold', label: __('On hold') },
    { value: 'cancelled', label: __('Canceled') },
];

interface OrderStatusFilterProps {
    active: string | null;
    onChange: (value: string | null) => void;
}

export function OrderStatusFilter({ active, onChange }: OrderStatusFilterProps) {
    return (
        <div role="group" aria-label={__('Filter orders by status')} className="flex flex-wrap gap-2">
            {FILTERS.map((filter) => {
                const isActive = active === filter.value;

                return (
                    <button
                        key={filter.value ?? 'all'}
                        type="button"
                        aria-pressed={isActive}
                        onClick={() => onChange(filter.value)}
                        className={cn(
                            'h-9 rounded-md border px-4 text-sm font-semibold transition-colors',
                            isActive
                                ? 'border-primary bg-primary text-white'
                                : 'border-line-strong bg-surface text-body transition-colors can-hover:hover:border-primary can-hover:hover:text-primary',
                        )}
                    >
                        {filter.label}
                    </button>
                );
            })}
        </div>
    );
}
