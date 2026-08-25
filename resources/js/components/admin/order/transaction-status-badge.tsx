import { StatusBadge } from '@/components/admin/status-badge';
import { __ } from '@/lib/i18n';
import type { TransactionStatus } from '@/types';

const labelMap: Record<TransactionStatus, string> = {
    success: __('Success'),
    failed: __('Failed'),
    pending: __('Pending'),
};

export function TransactionStatusBadge({ status }: { status: TransactionStatus }) {
    return <StatusBadge status={status}>{labelMap[status]}</StatusBadge>;
}
