import PaymentWebhookController from '@/actions/App/Http/Controllers/PaymentWebhookController';
import { InfoPopover } from '@/components/admin/info-popover';
import { WebhookUrlField, useWebhookUrl } from '@/components/admin/webhook-url-field';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { __ } from '@/lib/i18n';
import type { PaymentGateway, RazorpayCredentials } from '@/types';

import { WebhookRefundSyncField } from './webhook-refund-sync-field';

interface RazorpayWebhookTabProps {
    paymentGateway?: PaymentGateway;
    errors: Record<string, string>;
}

export function RazorpayWebhookTab({ paymentGateway, errors }: RazorpayWebhookTabProps) {
    const credentials = paymentGateway?.credentials as RazorpayCredentials | null;
    const webhookUrl = useWebhookUrl(PaymentWebhookController('razorpay').url);

    return (
        <div className="mt-4 space-y-6">
            <WebhookUrlField
                url={webhookUrl}
                description={__('Add this webhook URL in your Razorpay Dashboard under Developers > Webhooks.')}
            />

            <Field>
                <FieldLabel>{__('Required events')}</FieldLabel>
                <ul className="space-y-1 text-xs text-muted-foreground">
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">payment.authorized</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">payment.captured</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">payment.failed</code>
                    </li>
                    <li>
                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">refund.processed</code>
                    </li>
                </ul>
            </Field>

            <Field>
                <div className="flex items-center gap-2">
                    <FieldLabel htmlFor="razorpay-webhook-secret">{__('Secret')}</FieldLabel>
                    <InfoPopover
                        className="w-80"
                        text={__(
                            'Enter a secret of your choice. Use the same value in the Secret field when creating the webhook in your Razorpay Dashboard. This is used to verify webhook signatures.',
                        )}
                    />
                </div>
                <Input
                    id="razorpay-webhook-secret"
                    name="credentials.webhook_secret"
                    defaultValue={credentials?.webhook_secret}
                    placeholder="..."
                    className="font-mono text-xs"
                />
                <FieldError>{errors['credentials.webhook_secret']}</FieldError>
            </Field>

            <WebhookRefundSyncField gatewayName="Razorpay" paymentGateway={paymentGateway} />
        </div>
    );
}
