import { CheckIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface FilterOptionProps {
    type: 'checkbox' | 'radio';
    checked: boolean;
    onSelect: () => void;
    label: ReactNode;
    count?: number;
}

export function FilterOption({ type, checked, onSelect, label, count }: FilterOptionProps) {
    return (
        <button
            type="button"
            role={type}
            aria-checked={checked}
            onClick={onSelect}
            className="flex items-center gap-2.5 rounded-xs py-1 text-muted transition-colors hover:text-primary lg:py-0"
        >
            <span
                aria-hidden="true"
                className={cn(
                    'flex h-[18px] w-[18px] shrink-0 items-center justify-center border text-white',
                    type === 'checkbox' ? 'rounded-xs' : 'rounded-full',
                    checked ? 'border-primary bg-primary' : 'border-line-strong bg-surface',
                )}
            >
                {checked &&
                    (type === 'checkbox' ? (
                        <CheckIcon size={11} strokeWidth={3.5} />
                    ) : (
                        <span className="h-2 w-2 rounded-full bg-white" />
                    ))}
            </span>
            <span className="truncate">{label}</span>
            {count != null && <span className="ms-auto text-xs text-muted">{count}</span>}
        </button>
    );
}
