import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { HelpLink } from '@/components/storefront/order/help-link';
import { cn } from '@/lib/utils';
import type { StorefrontSharedData } from '@/types';

export function OrderSummaryAside({ orderNumber, children }: { orderNumber: string; children: ReactNode }) {
    const stickyHeader = usePage<StorefrontSharedData>().props.storefront.header.sticky;

    return (
        <aside className={cn('flex flex-col gap-6 lg:sticky', stickyHeader ? 'lg:top-22' : 'lg:top-6')}>
            {children}
            <HelpLink orderNumber={orderNumber} />
        </aside>
    );
}
