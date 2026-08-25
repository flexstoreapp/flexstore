import { useState } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { PaymentGateway, StripeCredentials as StripeCredentialsType } from '@/types';

import { CheckoutModeField } from './checkout-mode-field';
import { TestConnectionButton } from './test-connection-button';

interface StripeCredentialsProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function StripeCredentials({ paymentGateway, errors }: StripeCredentialsProps) {
    const credentials = paymentGateway?.credentials as StripeCredentialsType | undefined;
    const [secretKey, setSecretKey] = useState(credentials?.secret_key ?? '');

    return (
        <>
            <Field>
                <FieldLabel htmlFor="stripe-publishable-key">{__('Publishable key')}</FieldLabel>
                <Input
                    id="stripe-publishable-key"
                    name="credentials.publishable_key"
                    defaultValue={credentials?.publishable_key}
                    placeholder="pk_live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.publishable_key']}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="stripe-secret-key">{__('Secret key')}</FieldLabel>
                <Input
                    id="stripe-secret-key"
                    name="credentials.secret_key"
                    value={secretKey}
                    onChange={(event) => setSecretKey(event.target.value)}
                    placeholder="sk_live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.secret_key']}</FieldError>
            </Field>

            <CheckoutModeField
                idPrefix="stripe"
                defaultValue={credentials?.checkout_mode}
                gatewayName="Stripe"
                embeddedDescription={__('Displays the payment form directly on your checkout page')}
                error={errors['credentials.checkout_mode']}
            />

            <TestConnectionButton
                driver="stripe"
                credentials={{ secret_key: secretKey }}
                disabled={secretKey.trim() === ''}
            />
        </>
    );
}
