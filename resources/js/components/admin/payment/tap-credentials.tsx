import { useState } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { TapCredentials, PaymentGateway } from '@/types';

import { CheckoutModeField } from './checkout-mode-field';
import { TestConnectionButton } from './test-connection-button';

interface TapCredentialsProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function TapCredentialsForm({ paymentGateway, errors }: TapCredentialsProps) {
    const credentials = paymentGateway?.credentials as TapCredentials | null;
    const [secretKey, setSecretKey] = useState(credentials?.secret_key ?? '');

    return (
        <>
            <Field>
                <FieldLabel htmlFor="tap-public-key">{__('Public key')}</FieldLabel>
                <Input
                    id="tap-public-key"
                    name="credentials.public_key"
                    defaultValue={credentials?.public_key}
                    placeholder="pk_live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.public_key']}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="tap-secret-key">{__('Secret key')}</FieldLabel>
                <Input
                    id="tap-secret-key"
                    name="credentials.secret_key"
                    value={secretKey}
                    onChange={(event) => setSecretKey(event.target.value)}
                    placeholder="sk_live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.secret_key']}</FieldError>
            </Field>

            <CheckoutModeField
                idPrefix="tap"
                defaultValue={credentials?.checkout_mode}
                gatewayName="Tap"
                embeddedDescription={__('Shows the Tap card form on your checkout page')}
                error={errors['credentials.checkout_mode']}
            />

            <TestConnectionButton
                driver="tap"
                credentials={{ secret_key: secretKey }}
                disabled={secretKey.trim() === ''}
            />
        </>
    );
}
