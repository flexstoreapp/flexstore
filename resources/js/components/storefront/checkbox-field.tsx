import type { ReactNode } from 'react';

import { Checkbox } from '@/components/storefront/checkbox';
import { cn } from '@/lib/utils';

interface CheckboxFieldProps {
    id: string;
    label: ReactNode;
    checked: boolean;
    onChange: (checked: boolean) => void;
    className?: string;
}

export function CheckboxField({ id, label, checked, onChange, className }: CheckboxFieldProps) {
    return (
        <label htmlFor={id} className={cn('flex w-fit items-center gap-2.5 rounded-xs text-base text-body', className)}>
            <Checkbox id={id} checked={checked} onChange={onChange} />
            {label}
        </label>
    );
}
