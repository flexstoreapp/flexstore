import { useState } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { MercadoPagoCredentials, PaymentGateway } from '@/types';

import { CheckoutModeField } from './checkout-mode-field';
import { TestConnectionButton } from './test-connection-button';

interface MercadoPagoCredentialsProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function MercadoPagoCredentialsForm({ paymentGateway, errors }: MercadoPagoCredentialsProps) {
    const credentials = paymentGateway?.credentials as MercadoPagoCredentials | null;
    const [accessToken, setAccessToken] = useState(credentials?.access_token ?? '');

    return (
        <>
            <Field>
                <FieldLabel htmlFor="mercadopago-public-key">{__('Public key')}</FieldLabel>
                <Input
                    id="mercadopago-public-key"
                    name="credentials.public_key"
                    defaultValue={credentials?.public_key}
                    placeholder="APP_USR-..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.public_key']}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="mercadopago-access-token">{__('Access token')}</FieldLabel>
                <Input
                    id="mercadopago-access-token"
                    name="credentials.access_token"
                    value={accessToken}
                    onChange={(event) => setAccessToken(event.target.value)}
                    placeholder="APP_USR-..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.access_token']}</FieldError>
            </Field>

            <CheckoutModeField
                idPrefix="mercadopago"
                defaultValue={credentials?.checkout_mode}
                gatewayName="Mercado Pago"
                embeddedDescription={__('Opens the Mercado Pago payment modal on your checkout page')}
                error={errors['credentials.checkout_mode']}
            />

            <TestConnectionButton
                driver="mercadopago"
                credentials={{ access_token: accessToken }}
                disabled={accessToken.trim() === ''}
            />
        </>
    );
}
