import type { ChangeEvent } from 'react';

import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

interface TextFieldProps {
    id: string;
    label: string;
    name?: string;
    value?: string;
    defaultValue?: string;
    onChange?: (value: string) => void;
    onBlur?: () => void;
    error?: string;
    optional?: boolean;
    type?: 'text' | 'email' | 'tel';
    inputMode?: 'text' | 'numeric' | 'email' | 'tel';
    autoComplete?: string;
    autoFocus?: boolean;
    readOnly?: boolean;
    selectOnFocus?: boolean;
    required?: boolean;
    placeholder?: string;
    className?: string;
}

export function TextField({
    id,
    label,
    name,
    value,
    defaultValue,
    onChange,
    onBlur,
    error,
    optional = false,
    type = 'text',
    inputMode,
    autoComplete,
    autoFocus,
    readOnly,
    selectOnFocus,
    required,
    placeholder,
    className,
}: TextFieldProps) {
    const controlled = onChange
        ? { value: value ?? '', onChange: (event: ChangeEvent<HTMLInputElement>) => onChange(event.target.value) }
        : { defaultValue };

    return (
        <div className={className}>
            <label htmlFor={id} className="mb-2 block text-sm font-semibold text-ink">
                {label}
                {optional && <span className="ms-1 font-normal text-muted">({__('optional')})</span>}
            </label>
            <input
                id={id}
                name={name}
                type={type}
                inputMode={inputMode}
                autoComplete={autoComplete}
                autoFocus={autoFocus}
                readOnly={readOnly}
                required={required}
                placeholder={placeholder}
                onBlur={onBlur}
                onFocus={selectOnFocus ? (event) => event.currentTarget.select() : undefined}
                onClick={selectOnFocus ? (event) => event.currentTarget.select() : undefined}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                className={cn(
                    'h-11 w-full rounded-md border bg-surface-2 px-4 text-ink transition placeholder:text-muted focus-visible:outline-hidden',
                    error
                        ? 'border-error ring-1 ring-error'
                        : 'border-line-strong focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-primary',
                    readOnly && (selectOnFocus ? 'cursor-text' : 'cursor-not-allowed'),
                )}
                {...controlled}
            />
            {error && (
                <p id={`${id}-error`} className="mt-1.5 mb-0 text-sm text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
