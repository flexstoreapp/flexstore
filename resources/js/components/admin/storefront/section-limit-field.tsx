import { useState } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Slider } from '@/components/ui/slider';

interface SectionLimitFieldProps {
    label: (limit: number) => string;
    name?: string;
    defaultValue?: number;
    min?: number;
    max?: number;
    step?: number;
    errors: Record<string, string>;
}

export function SectionLimitField({
    label,
    name = 'settings.product_limit',
    defaultValue = 8,
    min = 1,
    max = 50,
    step = 1,
    errors,
}: SectionLimitFieldProps) {
    const [limit, setLimit] = useState(defaultValue);

    return (
        <Field>
            <FieldLabel htmlFor={`${name.replace('.', '-')}`}>{label(limit)}</FieldLabel>
            <Slider
                id={`${name.replace('.', '-')}`}
                name={name}
                value={[limit]}
                onValueChange={([value]) => setLimit(value)}
                min={min}
                max={max}
                step={step}
            />
            <FieldError>{errors[name]}</FieldError>
        </Field>
    );
}
