import { StatusBadge } from '@/components/admin/status-badge';
import { useFormatId } from '@/hooks/use-format-id';
import { __ } from '@/lib/i18n';
import type { Order } from '@/types';

export function OrderTitle({ order }: { order: Order }) {
    const formatId = useFormatId();

    return (
        <span className="flex items-center gap-2">
            {formatId(order.id)}
            {order.canceled_at && <StatusBadge status="canceled">{__('Canceled')}</StatusBadge>}
        </span>
    );
}
