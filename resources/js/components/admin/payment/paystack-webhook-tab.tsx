import PaymentWebhookController from '@/actions/App/Http/Controllers/PaymentWebhookController';
import { WebhookUrlField, useWebhookUrl } from '@/components/admin/webhook-url-field';
import { __ } from '@/lib/i18n';
import type { PaymentGateway } from '@/types';

import { WebhookRefundSyncField } from './webhook-refund-sync-field';

interface PaystackWebhookTabProps {
    paymentGateway?: PaymentGateway;
}

export function PaystackWebhookTab({ paymentGateway }: PaystackWebhookTabProps) {
    const webhookUrl = useWebhookUrl(PaymentWebhookController('paystack').url);

    return (
        <div className="mt-4 space-y-6">
            <WebhookUrlField
                url={webhookUrl}
                description={__(
                    'Add this webhook URL in your Paystack Dashboard under Settings > API Keys & Webhooks.',
                )}
            />

            <WebhookRefundSyncField gatewayName="Paystack" paymentGateway={paymentGateway} />
        </div>
    );
}
