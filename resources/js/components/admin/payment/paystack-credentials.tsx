import { useState } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { PaystackCredentials, PaymentGateway } from '@/types';

import { CheckoutModeField } from './checkout-mode-field';
import { TestConnectionButton } from './test-connection-button';

interface PaystackCredentialsProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function PaystackCredentialsForm({ paymentGateway, errors }: PaystackCredentialsProps) {
    const credentials = paymentGateway?.credentials as PaystackCredentials | null;
    const [secretKey, setSecretKey] = useState(credentials?.secret_key ?? '');

    return (
        <>
            <Field>
                <FieldLabel htmlFor="paystack-public-key">{__('Public key')}</FieldLabel>
                <Input
                    id="paystack-public-key"
                    name="credentials.public_key"
                    defaultValue={credentials?.public_key}
                    placeholder="pk_live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.public_key']}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="paystack-secret-key">{__('Secret key')}</FieldLabel>
                <Input
                    id="paystack-secret-key"
                    name="credentials.secret_key"
                    value={secretKey}
                    onChange={(event) => setSecretKey(event.target.value)}
                    placeholder="sk_live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.secret_key']}</FieldError>
            </Field>

            <CheckoutModeField
                idPrefix="paystack"
                defaultValue={credentials?.checkout_mode}
                gatewayName="Paystack"
                embeddedDescription={__('Opens the Paystack payment modal on your checkout page')}
                error={errors['credentials.checkout_mode']}
            />

            <TestConnectionButton
                driver="paystack"
                credentials={{ secret_key: secretKey }}
                disabled={secretKey.trim() === ''}
            />
        </>
    );
}
