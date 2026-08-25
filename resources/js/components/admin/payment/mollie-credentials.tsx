import { useState } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { MollieCredentials, PaymentGateway } from '@/types';

import { CheckoutModeField } from './checkout-mode-field';
import { TestConnectionButton } from './test-connection-button';

interface MollieCredentialsProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function MollieCredentialsForm({ paymentGateway, errors }: MollieCredentialsProps) {
    const credentials = paymentGateway?.credentials as MollieCredentials | null;
    const [apiKey, setApiKey] = useState(credentials?.api_key ?? '');

    return (
        <>
            <Field>
                <FieldLabel htmlFor="mollie-api-key">{__('API key')}</FieldLabel>
                <Input
                    id="mollie-api-key"
                    name="credentials.api_key"
                    value={apiKey}
                    onChange={(event) => setApiKey(event.target.value)}
                    placeholder="live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.api_key']}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="mollie-profile-id">{__('Profile ID')}</FieldLabel>
                <Input
                    id="mollie-profile-id"
                    name="credentials.profile_id"
                    defaultValue={credentials?.profile_id}
                    placeholder="pfl_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.profile_id']}</FieldError>
            </Field>

            <CheckoutModeField
                idPrefix="mollie"
                defaultValue={credentials?.checkout_mode}
                gatewayName="Mollie"
                embeddedDescription={__('Displays the credit card fields directly on your checkout page')}
                error={errors['credentials.checkout_mode']}
            />

            <TestConnectionButton driver="mollie" credentials={{ api_key: apiKey }} disabled={apiKey.trim() === ''} />
        </>
    );
}
