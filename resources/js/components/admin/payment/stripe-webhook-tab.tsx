import PaymentWebhookController from '@/actions/App/Http/Controllers/PaymentWebhookController';
import { InfoPopover } from '@/components/admin/info-popover';
import { WebhookUrlField, useWebhookUrl } from '@/components/admin/webhook-url-field';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { PaymentGateway, StripeCredentials } from '@/types';

import { WebhookRefundSyncField } from './webhook-refund-sync-field';

interface StripeWebhookTabProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function StripeWebhookTab({ paymentGateway, errors }: StripeWebhookTabProps) {
    const credentials = paymentGateway?.credentials as StripeCredentials | undefined;
    const webhookUrl = useWebhookUrl(PaymentWebhookController('stripe').url);

    return (
        <div className="mt-4 space-y-6">
            <WebhookUrlField
                url={webhookUrl}
                description={__('Add this webhook URL in your Stripe Dashboard under Developers > Webhooks.')}
            />

            <Field>
                <FieldLabel>{__('Required events')}</FieldLabel>
                <ul className="space-y-1 text-xs text-muted-foreground">
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">checkout.session.completed</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">payment_intent.succeeded</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">payment_intent.payment_failed</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">charge.refunded</code>
                    </li>
                </ul>
            </Field>

            <Field>
                <div className="flex items-center gap-2">
                    <FieldLabel htmlFor="signing-secret">{__('Signing secret')}</FieldLabel>
                    <InfoPopover
                        className="w-80"
                        text={__(
                            'After creating a webhook endpoint in your Stripe Dashboard, click on the endpoint to reveal the signing secret. This is used to verify webhook signatures.',
                        )}
                    />
                </div>
                <Input
                    id="signing-secret"
                    name="credentials.signing_secret"
                    defaultValue={credentials?.signing_secret}
                    placeholder="whsec_..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.webhook_secret']}</FieldError>
            </Field>

            <WebhookRefundSyncField gatewayName="Stripe" paymentGateway={paymentGateway} />
        </div>
    );
}
