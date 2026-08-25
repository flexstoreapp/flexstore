import { CheckboxCard } from '@/components/ui/checkbox-card';
import { __ } from '@/lib/i18n';
import type { PaymentGateway } from '@/types';

interface WebhookRefundSyncFieldProps {
    gatewayName: string;
    paymentGateway?: PaymentGateway;
}

export function WebhookRefundSyncField({ gatewayName, paymentGateway }: WebhookRefundSyncFieldProps) {
    return (
        <CheckboxCard
            id="sync_external_refunds"
            name="sync_external_refunds"
            label={__('Sync external refunds')}
            description={__('Sync refunds issued from the :gateway dashboard', { gateway: gatewayName })}
            defaultChecked={paymentGateway?.sync_external_refunds ?? false}
        />
    );
}
