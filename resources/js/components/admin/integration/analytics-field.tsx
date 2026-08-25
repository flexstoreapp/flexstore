import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

interface AnalyticsFieldProps {
    name: string;
    label: string;
    placeholder: string;
    defaultValue: string | null;
    error?: string;
}

export function AnalyticsField({ name, label, placeholder, defaultValue, error }: AnalyticsFieldProps) {
    return (
        <Field>
            <FieldLabel htmlFor={name}>{label}</FieldLabel>
            <Input
                id={name}
                name={name}
                type="text"
                placeholder={placeholder}
                defaultValue={defaultValue ?? ''}
                autoFocus
            />
            <FieldError>{error}</FieldError>
        </Field>
    );
}
