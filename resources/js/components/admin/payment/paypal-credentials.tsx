import { useState } from 'react';

import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { PaymentGateway, PaypalCredentials } from '@/types';

import { CheckoutModeField } from './checkout-mode-field';
import { TestConnectionButton } from './test-connection-button';

interface PaypalCredentialsProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function PaypalCredentialsForm({ paymentGateway, errors }: PaypalCredentialsProps) {
    const credentials = paymentGateway?.credentials as PaypalCredentials | null;
    const [clientId, setClientId] = useState(credentials?.client_id ?? '');
    const [clientSecret, setClientSecret] = useState(credentials?.client_secret ?? '');
    const [sandbox, setSandbox] = useState(credentials?.sandbox ?? true);

    return (
        <>
            <Field>
                <FieldLabel htmlFor="paypal-client-id">{__('Client ID')}</FieldLabel>
                <Input
                    id="paypal-client-id"
                    name="credentials.client_id"
                    value={clientId}
                    onChange={(event) => setClientId(event.target.value)}
                    placeholder="..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.client_id']}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="paypal-client-secret">{__('Client secret')}</FieldLabel>
                <Input
                    id="paypal-client-secret"
                    name="credentials.client_secret"
                    value={clientSecret}
                    onChange={(event) => setClientSecret(event.target.value)}
                    placeholder="..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.client_secret']}</FieldError>
            </Field>

            <CheckoutModeField
                idPrefix="paypal"
                defaultValue={credentials?.checkout_mode}
                gatewayName="PayPal"
                embeddedDescription={__('Displays the PayPal buttons directly on your checkout page')}
                error={errors['credentials.checkout_mode']}
            />

            <Field orientation="horizontal">
                <Checkbox
                    id="paypal-sandbox"
                    name="credentials.sandbox"
                    checked={sandbox}
                    onCheckedChange={(checked) => setSandbox(checked === true)}
                />
                <FieldLabel htmlFor="paypal-sandbox" className="font-normal">
                    {__('Sandbox mode')}
                </FieldLabel>
                <FieldError>{errors['credentials.sandbox']}</FieldError>
            </Field>

            <TestConnectionButton
                driver="paypal"
                credentials={{ client_id: clientId, client_secret: clientSecret, sandbox }}
                disabled={clientId.trim() === '' || clientSecret.trim() === ''}
            />
        </>
    );
}
