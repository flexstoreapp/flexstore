import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { Customer } from '@/types';

interface CustomerFormBasicInfoProps {
    customer?: Customer;
    errors: Record<string, string>;
}

export function CustomerFormBasicInfo({ customer, errors }: CustomerFormBasicInfoProps) {
    return (
        <div className="space-y-6">
            <Field>
                <FieldLabel htmlFor="name">{__('Full name')}</FieldLabel>
                <Input id="name" name="name" type="text" defaultValue={customer?.name ?? ''} />
                <FieldError>{errors.name}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="email">{__('Email address')}</FieldLabel>
                <Input id="email" name="email" type="email" defaultValue={customer?.email ?? ''} />
                <FieldError>{errors.email}</FieldError>
            </Field>

            <Field>
                <Field>
                    <FieldLabel htmlFor="password">{customer ? __('New password') : __('Password')}</FieldLabel>
                    <Input id="password" name="password" type="password" />
                </Field>
                {customer && <FieldDescription>{__('Leave blank to keep current password.')}</FieldDescription>}
                <FieldError>{errors.password}</FieldError>
            </Field>
        </div>
    );
}
