import { ChevronDownIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

interface SelectFieldProps {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    options: { value: string; label: string }[];
    error?: string;
    placeholder?: string;
    autoComplete?: string;
    className?: string;
}

export function SelectField({
    id,
    label,
    value,
    onChange,
    options,
    error,
    placeholder,
    autoComplete,
    className,
}: SelectFieldProps) {
    return (
        <div className={className}>
            <label htmlFor={id} className="mb-2 block text-sm font-semibold text-ink">
                {label}
            </label>
            <div className="relative">
                <select
                    id={id}
                    value={value}
                    onChange={(event) => onChange(event.target.value)}
                    autoComplete={autoComplete}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={cn(
                        'h-11 w-full appearance-none rounded-md border bg-surface-2 ps-4 pe-11 text-ink transition focus-visible:outline-hidden',
                        error
                            ? 'border-error ring-1 ring-error'
                            : 'border-line-strong focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-primary',
                    )}
                >
                    {placeholder && <option value="">{placeholder}</option>}
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <ChevronDownIcon
                    size={16}
                    aria-hidden="true"
                    className="pointer-events-none absolute end-4 top-1/2 -translate-y-1/2 text-muted"
                />
            </div>
            {error && (
                <p id={`${id}-error`} className="mt-1.5 mb-0 text-sm text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
