import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { ShippingCarrier } from '@/types';

interface ShippingCarrierGeneralTabContentProps {
    shippingCarrier?: ShippingCarrier;
    fallbackName?: string;
    errors: Record<string, string>;
    className?: string;
    credentialFields?: (props: {
        shippingCarrier?: ShippingCarrier;
        errors: Record<string, string>;
    }) => React.ReactNode;
}

export function ShippingCarrierGeneralTabContent({
    shippingCarrier,
    fallbackName,
    errors,
    className = 'mt-4 space-y-6',
    credentialFields,
}: ShippingCarrierGeneralTabContentProps) {
    return (
        <div className={cn(className)}>
            <Field>
                <FieldLabel htmlFor="name">{__('Shipping carrier name')}</FieldLabel>
                <Input
                    id="name"
                    name="name"
                    defaultValue={getTranslation(shippingCarrier?.name, fallbackName)}
                    required
                />
                <FieldError>{errors.name}</FieldError>
            </Field>

            {credentialFields?.({ shippingCarrier, errors })}

            <Field orientation="horizontal">
                <Checkbox id="is_active" name="is_active" defaultChecked={shippingCarrier?.is_active ?? true} />
                <FieldLabel htmlFor="is_active">{__('Active')}</FieldLabel>
            </Field>
        </div>
    );
}
