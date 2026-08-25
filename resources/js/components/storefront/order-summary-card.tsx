import type { ReactNode } from 'react';

import { __ } from '@/lib/i18n';

export function OrderSummaryCard({ title, children }: { title?: string; children: ReactNode }) {
    return (
        <aside aria-labelledby="order-summary-heading" className="rounded-md border border-line bg-surface p-6">
            <h2 id="order-summary-heading" className="m-0 text-xl font-semibold text-ink">
                {title ?? __('Order summary')}
            </h2>
            {children}
        </aside>
    );
}
