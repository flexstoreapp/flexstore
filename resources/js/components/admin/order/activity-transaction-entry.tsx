import { ChevronDownIcon } from 'lucide-react';
import { useState } from 'react';

import { ActivityTimestamp } from '@/components/admin/activity-timestamp';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { OrderTransaction, TransactionType } from '@/types';

import { TransactionDetails } from './transaction-details';
import { TransactionStatusBadge } from './transaction-status-badge';

function getTransactionTypeLabel(type: TransactionType): string {
    return {
        sale: __('Payment'),
        void: __('Void'),
        refund: __('Refund'),
    }[type];
}

export function ActivityTransactionEntry({ transaction }: { transaction: OrderTransaction }) {
    const { formatMoney } = useFormatMoney();
    const [expanded, setExpanded] = useState(false);

    const hasDetails =
        !!transaction.payment_method ||
        !!transaction.gateway_reference ||
        !!transaction.failure_reason ||
        transaction.is_manual_entry;

    return (
        <div>
            <div className="flex items-start justify-between gap-3">
                <button
                    type="button"
                    className={cn('flex flex-wrap items-center gap-2 pt-0.5 text-sm', hasDetails && 'cursor-pointer')}
                    onClick={() => hasDetails && setExpanded(!expanded)}
                    disabled={!hasDetails}
                >
                    <span>{getTransactionTypeLabel(transaction.type)}</span>
                    <span>{formatMoney(transaction.amount, transaction.currency_code)}</span>
                    {transaction.status !== 'success' && <TransactionStatusBadge status={transaction.status} />}
                    {hasDetails && (
                        <ChevronDownIcon
                            className={cn(
                                'size-3.5 text-muted-foreground transition-transform duration-200',
                                expanded && 'rotate-180',
                            )}
                        />
                    )}
                </button>
                <ActivityTimestamp date={transaction.created_at} />
            </div>

            <div
                className="grid transition-[grid-template-rows] duration-200"
                style={{ gridTemplateRows: expanded ? '1fr' : '0fr' }}
            >
                <div className="overflow-hidden">
                    <div className="space-y-1.5 pt-3">
                        <TransactionDetails transaction={transaction} />
                        {transaction.failure_reason && (
                            <p className="text-xs text-destructive">{transaction.failure_reason}</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
