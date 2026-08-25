import { useState } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { PaymentGateway, RazorpayCredentials } from '@/types';

import { CheckoutModeField } from './checkout-mode-field';
import { TestConnectionButton } from './test-connection-button';

interface RazorpayCredentialsProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function RazorpayCredentialsForm({ paymentGateway, errors }: RazorpayCredentialsProps) {
    const credentials = paymentGateway?.credentials as RazorpayCredentials | null;
    const [keyId, setKeyId] = useState(credentials?.key_id ?? '');
    const [keySecret, setKeySecret] = useState(credentials?.key_secret ?? '');

    return (
        <>
            <Field>
                <FieldLabel htmlFor="razorpay-key-id">{__('Key ID')}</FieldLabel>
                <Input
                    id="razorpay-key-id"
                    name="credentials.key_id"
                    value={keyId}
                    onChange={(event) => setKeyId(event.target.value)}
                    placeholder="rzp_live_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.key_id']}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="razorpay-key-secret">{__('Key secret')}</FieldLabel>
                <Input
                    id="razorpay-key-secret"
                    name="credentials.key_secret"
                    value={keySecret}
                    onChange={(event) => setKeySecret(event.target.value)}
                    placeholder="..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.key_secret']}</FieldError>
            </Field>

            <CheckoutModeField
                idPrefix="razorpay"
                defaultValue={credentials?.checkout_mode}
                gatewayName="Razorpay"
                embeddedDescription={__('Opens the Razorpay payment modal on your checkout page')}
                error={errors['credentials.checkout_mode']}
            />

            <TestConnectionButton
                driver="razorpay"
                credentials={{ key_id: keyId, key_secret: keySecret }}
                disabled={keyId.trim() === '' || keySecret.trim() === ''}
            />
        </>
    );
}
