import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { __ } from '@/lib/i18n';
import type { CheckoutMode } from '@/types';

interface CheckoutModeFieldProps {
    idPrefix: string;
    gatewayName: string;
    defaultValue?: CheckoutMode;
    embeddedDescription: string;
    error?: string;
}

export function CheckoutModeField({
    idPrefix,
    gatewayName,
    defaultValue,
    embeddedDescription,
    error,
}: CheckoutModeFieldProps) {
    return (
        <Field>
            <FieldLabel htmlFor={`${idPrefix}-checkout-mode`}>{__('Checkout mode')}</FieldLabel>
            <RadioGroup
                name="credentials.checkout_mode"
                id={`${idPrefix}-checkout-mode`}
                defaultValue={defaultValue ?? 'embedded'}
            >
                <div className="flex items-center gap-2">
                    <RadioGroupItem value="embedded" id={`${idPrefix}-checkout-mode-embedded`} />
                    <FieldLabel htmlFor={`${idPrefix}-checkout-mode-embedded`} className="font-normal">
                        {__('Embedded checkout')}
                    </FieldLabel>
                </div>
                <p className="ms-6 -mt-2 text-xs text-muted-foreground">{embeddedDescription}</p>

                <div className="flex items-center gap-2">
                    <RadioGroupItem value="hosted" id={`${idPrefix}-checkout-mode-hosted`} />
                    <FieldLabel htmlFor={`${idPrefix}-checkout-mode-hosted`} className="font-normal">
                        {__('Hosted checkout')}
                    </FieldLabel>
                </div>
                <p className="ms-6 -mt-2 text-xs text-muted-foreground">
                    {__('Redirects customer to :gateway to complete payment', { gateway: gatewayName })}
                </p>
            </RadioGroup>
            <FieldError>{error}</FieldError>
        </Field>
    );
}
