import { CheckIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

interface CheckboxProps {
    id: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    className?: string;
}

export function Checkbox({ id, checked, onChange, className }: CheckboxProps) {
    return (
        <>
            <input
                id={id}
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="peer sr-only"
            />
            <span
                aria-hidden="true"
                className={cn(
                    'flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-xs border border-line-strong bg-surface text-transparent transition peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-primary',
                    className,
                )}
            >
                <CheckIcon size={11} strokeWidth={3} />
            </span>
        </>
    );
}
