import { EyeIcon, EyeOffIcon } from 'lucide-react';
import { useState, type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface PasswordFieldProps {
    id: string;
    label: string;
    name: string;
    error?: string;
    autoComplete?: string;
    autoFocus?: boolean;
    required?: boolean;
    placeholder?: string;
    labelAction?: ReactNode;
}

export function PasswordField({
    id,
    label,
    name,
    error,
    autoComplete,
    autoFocus,
    required,
    placeholder,
    labelAction,
}: PasswordFieldProps) {
    const [visible, setVisible] = useState(false);

    return (
        <div>
            <div className="mb-2 flex items-center justify-between">
                <label htmlFor={id} className="text-sm font-semibold text-ink">
                    {label}
                </label>
                {labelAction}
            </div>
            <div className="relative flex items-center">
                <input
                    id={id}
                    name={name}
                    type={visible ? 'text' : 'password'}
                    autoComplete={autoComplete}
                    autoFocus={autoFocus}
                    required={required}
                    placeholder={placeholder}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    className={cn(
                        'h-11 w-full rounded-md border bg-surface-2 ps-4 pe-12 text-ink transition placeholder:text-muted focus-visible:outline-hidden',
                        error
                            ? 'border-error ring-1 ring-error'
                            : 'border-line-strong focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-primary',
                    )}
                />
                <button
                    type="button"
                    onClick={() => setVisible((current) => !current)}
                    aria-label={visible ? 'Hide password' : 'Show password'}
                    className="absolute end-2 flex h-8 w-8 items-center justify-center rounded-xs text-muted transition can-hover:hover:text-ink"
                >
                    {visible ? <EyeOffIcon size={19} strokeWidth={1.7} /> : <EyeIcon size={19} strokeWidth={1.7} />}
                </button>
            </div>
            {error && (
                <p id={`${id}-error`} className="mt-1.5 mb-0 text-sm text-error">
                    {error}
                </p>
            )}
        </div>
    );
}
