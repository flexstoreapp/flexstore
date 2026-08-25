import { InfoIcon } from 'lucide-react';

import { AdaptiveSelect } from '@/components/ui/adaptive-select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError } from '@/components/ui/field';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { Order, PaymentGateway } from '@/types';

interface OrderFormPaymentProps {
    order?: Order;
    paymentGateways?: PaymentGateway[];
    errors: Record<string, string>;
}

export function OrderFormPayment({ order, paymentGateways, errors }: OrderFormPaymentProps) {
    const paymentGatewayOptions = paymentGateways?.map((gateway) => ({
        value: String(gateway.id),
        label: getTranslation(gateway.name),
    }));

    const hasPaymentGateways = (paymentGatewayOptions?.length ?? 0) > 0;

    return (
        <Card>
            <CardHeader>
                <CardTitle>{__('Payment')}</CardTitle>
                <CardDescription>{__('Choose a payment method for the order')}</CardDescription>
            </CardHeader>
            <CardContent>
                {hasPaymentGateways ? (
                    <Field>
                        <AdaptiveSelect
                            name="payment_gateway_id"
                            value={String(order?.payment_gateway_id ?? '')}
                            placeholder={__('Select a payment method')}
                            aria-label={__('Payment method')}
                            options={paymentGatewayOptions ?? []}
                        />
                        <FieldError>{errors.payment_gateway_id}</FieldError>
                    </Field>
                ) : (
                    <Alert>
                        <InfoIcon />
                        <AlertDescription>{__('No payment methods are available.')}</AlertDescription>
                    </Alert>
                )}
            </CardContent>
        </Card>
    );
}
