import PaymentWebhookController from '@/actions/App/Http/Controllers/PaymentWebhookController';
import { InfoPopover } from '@/components/admin/info-popover';
import { WebhookUrlField, useWebhookUrl } from '@/components/admin/webhook-url-field';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { MercadoPagoCredentials, PaymentGateway } from '@/types';

import { WebhookRefundSyncField } from './webhook-refund-sync-field';

interface MercadoPagoWebhookTabProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function MercadoPagoWebhookTab({ paymentGateway, errors }: MercadoPagoWebhookTabProps) {
    const credentials = paymentGateway?.credentials as MercadoPagoCredentials | null;
    const webhookUrl = useWebhookUrl(PaymentWebhookController('mercadopago').url);

    return (
        <div className="mt-4 space-y-6">
            <WebhookUrlField
                url={webhookUrl}
                description={__(
                    'Add this webhook URL in your Mercado Pago Dashboard under Your integrations > Webhooks.',
                )}
            />

            <Field>
                <FieldLabel>{__('Required events')}</FieldLabel>
                <ul className="space-y-1 text-xs text-muted-foreground">
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">Payments</code>
                    </li>
                </ul>
            </Field>

            <Field>
                <div className="flex items-center gap-2">
                    <FieldLabel htmlFor="mercadopago-webhook-secret">{__('Secret')}</FieldLabel>
                    <InfoPopover
                        className="w-80"
                        text={__(
                            'Copy the secret signature shown in your Mercado Pago Dashboard when configuring the webhook. This is used to verify webhook signatures.',
                        )}
                    />
                </div>
                <Input
                    id="mercadopago-webhook-secret"
                    name="credentials.webhook_secret"
                    defaultValue={credentials?.webhook_secret}
                    placeholder="..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.webhook_secret']}</FieldError>
            </Field>

            <WebhookRefundSyncField gatewayName="Mercado Pago" paymentGateway={paymentGateway} />
        </div>
    );
}
