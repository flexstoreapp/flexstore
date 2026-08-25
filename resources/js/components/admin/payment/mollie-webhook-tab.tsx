import PaymentWebhookController from '@/actions/App/Http/Controllers/PaymentWebhookController';
import { WebhookUrlField, useWebhookUrl } from '@/components/admin/webhook-url-field';
import { __ } from '@/lib/i18n';
import type { PaymentGateway } from '@/types';

import { WebhookRefundSyncField } from './webhook-refund-sync-field';

interface MollieWebhookTabProps {
    paymentGateway?: PaymentGateway;
}

export function MollieWebhookTab({ paymentGateway }: MollieWebhookTabProps) {
    const webhookUrl = useWebhookUrl(PaymentWebhookController('mollie').url);

    return (
        <div className="mt-4 space-y-6">
            <WebhookUrlField
                url={webhookUrl}
                description={__(
                    'Mollie configures this webhook URL automatically for each payment. No manual setup is required.',
                )}
            />

            <WebhookRefundSyncField gatewayName="Mollie" paymentGateway={paymentGateway} />
        </div>
    );
}
