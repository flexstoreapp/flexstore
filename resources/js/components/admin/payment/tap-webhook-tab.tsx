import PaymentWebhookController from '@/actions/App/Http/Controllers/PaymentWebhookController';
import { WebhookUrlField, useWebhookUrl } from '@/components/admin/webhook-url-field';
import { __ } from '@/lib/i18n';
import type { PaymentGateway } from '@/types';

import { WebhookRefundSyncField } from './webhook-refund-sync-field';

interface TapWebhookTabProps {
    paymentGateway?: PaymentGateway;
}

export function TapWebhookTab({ paymentGateway }: TapWebhookTabProps) {
    const webhookUrl = useWebhookUrl(PaymentWebhookController('tap').url);

    return (
        <div className="mt-4 space-y-6">
            <WebhookUrlField
                url={webhookUrl}
                description={__('Add this webhook URL in your Tap Dashboard under Developers > Webhooks.')}
            />

            <WebhookRefundSyncField gatewayName="Tap" paymentGateway={paymentGateway} />
        </div>
    );
}
