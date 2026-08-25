import { Button } from '@/components/ui/button';
import { __ } from '@/lib/i18n';
import type { Order } from '@/types';

import { type ResendPayload, useResendNotification } from './use-resend-notification';

interface ResendEmailButtonProps {
    order: Order;
    title: string;
    payload: ResendPayload;
}

export function ResendEmailButton({ order, title, payload }: ResendEmailButtonProps) {
    const resend = useResendNotification(order);

    return (
        <Button variant="outline" size="sm" onClick={() => resend(title, payload)}>
            {__('Resend email')}
        </Button>
    );
}
