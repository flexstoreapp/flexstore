import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Field, FieldLabel } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import type { SectionTextColor } from '@/types';

interface TextColorFieldProps {
    name: string;
    value?: SectionTextColor;
    onChange: (value: SectionTextColor) => void;
}

export function TextColorField({ name, value, onChange }: TextColorFieldProps) {
    return (
        <Field>
            <FieldLabel htmlFor={`${name.replaceAll('.', '-')}-select`}>{__('Text color')}</FieldLabel>
            <AdaptiveSelect
                id={`${name.replaceAll('.', '-')}-select`}
                name={name}
                value={value ?? 'dark'}
                onValueChange={(next) => onChange(next as SectionTextColor)}
                options={[
                    { value: 'dark', label: __('Dark text') },
                    { value: 'light', label: __('Light text') },
                ]}
            />
        </Field>
    );
}
