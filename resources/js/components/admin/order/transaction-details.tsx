import { CheckIcon, CopyIcon } from 'lucide-react';

import { useClipboard } from '@/hooks/use-clipboard';
import { __ } from '@/lib/i18n';
import type { OrderTransaction } from '@/types';

export function TransactionDetails({ transaction }: { transaction: OrderTransaction }) {
    const [copiedText, copy] = useClipboard();
    const details = transaction.payment_method_details;
    const brand = details && 'brand' in details ? String(details.brand) : null;
    const last4 = details && 'last4' in details ? String(details.last4) : null;
    const isCopied = copiedText === transaction.gateway_reference;

    return (
        <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
            {transaction.is_manual_entry && <span>{__('Manually recorded')}</span>}
            {transaction.payment_method && (
                <>
                    {transaction.is_manual_entry && <span>&middot;</span>}
                    <div className="flex items-center gap-1.5">
                        <span className="capitalize">{transaction.payment_method}</span>
                        {brand && <span className="text-muted-foreground capitalize">({brand})</span>}
                        {last4 && <span className="text-muted-foreground">&bull;&bull;&bull;&bull; {last4}</span>}
                    </div>
                </>
            )}
            {transaction.gateway_reference && (
                <>
                    {(transaction.is_manual_entry || transaction.payment_method) && <span>&middot;</span>}
                    <div
                        className="inline-flex cursor-pointer items-center gap-1 rounded p-0.5 transition-colors hover:bg-muted/50"
                        onClick={() => copy(transaction.gateway_reference!)}
                    >
                        <code className="truncate font-mono text-xs">{transaction.gateway_reference}</code>
                        {isCopied ? (
                            <CheckIcon className="size-3 shrink-0 text-muted-foreground" />
                        ) : (
                            <CopyIcon className="size-3 shrink-0 text-muted-foreground" />
                        )}
                    </div>
                </>
            )}
        </div>
    );
}
