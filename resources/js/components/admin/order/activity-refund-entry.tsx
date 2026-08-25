import { ChevronDownIcon } from 'lucide-react';
import { type ReactNode, useState } from 'react';

import { ActivityTimestamp } from '@/components/admin/activity-timestamp';
import { StatusBadge } from '@/components/admin/status-badge';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { getStatusLabel } from '@/lib/order-utils';
import { cn } from '@/lib/utils';
import type { OrderRefund, OrderTransaction } from '@/types';

import { CollapsibleSection } from './collapsible-section';
import { TransactionDetails } from './transaction-details';
import { RefundBreakdown } from '../refund/refund-breakdown';

interface ActivityRefundEntryProps {
    refund: OrderRefund;
    currencyCode: string;
    transaction?: OrderTransaction;
    resend?: ReactNode;
}

export function ActivityRefundEntry({ refund, currencyCode, transaction, resend }: ActivityRefundEntryProps) {
    const { formatMoney } = useFormatMoney();
    const [expanded, setExpanded] = useState(false);

    const hasDetails =
        (transaction !== null && transaction !== undefined) ||
        !!refund.reason ||
        (refund.items && refund.items.length > 0) ||
        !!resend;

    return (
        <div>
            <div className="flex items-start justify-between gap-3">
                <button
                    type="button"
                    className={cn('flex flex-wrap items-center gap-2 pt-0.5 text-sm', hasDetails && 'cursor-pointer')}
                    onClick={() => hasDetails && setExpanded(!expanded)}
                    disabled={!hasDetails}
                >
                    <span>{__('Refund')}</span>
                    <span>{formatMoney(refund.amount, currencyCode)}</span>
                    {refund.status !== 'completed' && (
                        <StatusBadge status={refund.status}>{getStatusLabel(refund.status)}</StatusBadge>
                    )}
                    {hasDetails && (
                        <ChevronDownIcon
                            className={cn(
                                'size-3.5 text-muted-foreground transition-transform duration-200',
                                expanded && 'rotate-180',
                            )}
                        />
                    )}
                </button>
                <ActivityTimestamp date={refund.created_at} />
            </div>

            <CollapsibleSection open={expanded}>
                <div className="space-y-3 pt-3">
                    {transaction && <TransactionDetails transaction={transaction} />}
                    {refund.reason && <p className="text-xs text-muted-foreground">{refund.reason}</p>}
                    {transaction?.failure_reason && (
                        <p className="text-xs text-destructive">{transaction.failure_reason}</p>
                    )}
                    {refund.items && refund.items.length > 0 && (
                        <div className="text-sm">
                            <RefundBreakdown refund={refund} currencyCode={currencyCode} />
                        </div>
                    )}
                    {resend}
                </div>
            </CollapsibleSection>
        </div>
    );
}
