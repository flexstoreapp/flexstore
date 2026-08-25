import PaymentWebhookController from '@/actions/App/Http/Controllers/PaymentWebhookController';
import { InfoPopover } from '@/components/admin/info-popover';
import { WebhookUrlField, useWebhookUrl } from '@/components/admin/webhook-url-field';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { PaymentGateway, PaypalCredentials } from '@/types';

import { WebhookRefundSyncField } from './webhook-refund-sync-field';

interface PaypalWebhookTabProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function PaypalWebhookTab({ paymentGateway, errors }: PaypalWebhookTabProps) {
    const credentials = paymentGateway?.credentials as PaypalCredentials | null;
    const webhookUrl = useWebhookUrl(PaymentWebhookController('paypal').url);

    return (
        <div className="mt-4 space-y-6">
            <WebhookUrlField
                url={webhookUrl}
                description={__('Add this webhook URL in your PayPal Developer Dashboard under Webhooks.')}
            />

            <Field>
                <FieldLabel>{__('Required events')}</FieldLabel>
                <ul className="space-y-1 text-xs text-muted-foreground">
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">PAYMENT.CAPTURE.COMPLETED</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">PAYMENT.CAPTURE.DENIED</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">PAYMENT.CAPTURE.REFUNDED</code>
                    </li>
                </ul>
            </Field>

            <Field>
                <div className="flex items-center gap-2">
                    <FieldLabel htmlFor="paypal-webhook-id">{__('Webhook ID')}</FieldLabel>
                    <InfoPopover
                        className="w-80"
                        text={__(
                            'After creating a webhook in your PayPal Developer Dashboard, PayPal will display a Webhook ID. This is used to verify webhook signatures.',
                        )}
                    />
                </div>
                <Input
                    id="paypal-webhook-id"
                    name="credentials.webhook_id"
                    defaultValue={credentials?.webhook_id}
                    placeholder="..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.webhook_id']}</FieldError>
            </Field>

            <WebhookRefundSyncField gatewayName="PayPal" paymentGateway={paymentGateway} />
        </div>
    );
}
