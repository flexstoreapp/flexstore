import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { PaymentGateway } from '@/types';

interface PaymentGatewayGeneralTabContentProps {
    paymentGateway?: PaymentGateway;
    fallbackName?: string;
    errors: Record<string, string>;
    credentialFields?: (props: { paymentGateway?: PaymentGateway; errors: Record<string, string> }) => React.ReactNode;
}

export function PaymentGatewayGeneralTabContent({
    paymentGateway,
    fallbackName,
    errors,
    credentialFields,
}: PaymentGatewayGeneralTabContentProps) {
    return (
        <div className="mt-4 space-y-6">
            <Field>
                <FieldLabel htmlFor="name">{__('Payment gateway name')}</FieldLabel>
                <Input id="name" name="name" defaultValue={getTranslation(paymentGateway?.name, fallbackName)} />
                <FieldError>{errors.name}</FieldError>
            </Field>

            {credentialFields?.({ paymentGateway, errors })}

            <Field orientation="horizontal">
                <Checkbox id="is_active" name="is_active" defaultChecked={paymentGateway?.is_active ?? true} />
                <FieldLabel htmlFor="is_active">{__('Active')}</FieldLabel>
            </Field>
        </div>
    );
}
