import { cn, getTranslation } from '@/lib/utils';
import type { ProductBuyBoxOption } from '@/types';

interface ProductOptionsProps {
    options: ProductBuyBoxOption[];
    selected: Record<string, string>;
    onSelect: (optionId: string, valueId: string) => void;
}

export function ProductOptions({ options, selected, onSelect }: ProductOptionsProps) {
    if (options.length === 0) {
        return null;
    }

    return (
        <div className="mt-5 flex flex-col gap-5">
            {options.map((option) => (
                <div key={option.id}>
                    <span className="block text-sm font-semibold tracking-label text-ink uppercase">
                        {getTranslation(option.name)}
                    </span>
                    <div
                        className="mt-3 flex flex-wrap gap-3"
                        role="radiogroup"
                        aria-label={getTranslation(option.name)}
                    >
                        {option.values.map((value) => {
                            const isSelected = selected[option.id] === value.id;
                            return (
                                <button
                                    key={value.id}
                                    type="button"
                                    role="radio"
                                    aria-checked={isSelected}
                                    onClick={() => onSelect(option.id, value.id)}
                                    className={cn(
                                        'h-10 rounded-md border px-4 font-semibold transition focus-visible:outline-offset-2',
                                        isSelected
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-line-strong bg-surface-2 text-muted transition-colors can-hover:hover:border-primary can-hover:hover:bg-primary-tint can-hover:hover:text-primary',
                                    )}
                                >
                                    {getTranslation(value.value)}
                                </button>
                            );
                        })}
                    </div>
                </div>
            ))}
        </div>
    );
}
