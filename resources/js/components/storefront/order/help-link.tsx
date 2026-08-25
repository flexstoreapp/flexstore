import { usePage } from '@inertiajs/react';
import { MessageSquareIcon } from 'lucide-react';

import { __ } from '@/lib/i18n';
import type { StorefrontSharedData } from '@/types';

export function HelpLink({ orderNumber }: { orderNumber: string }) {
    const email = usePage<StorefrontSharedData>().props.storefront.store_email;

    if (!email) {
        return null;
    }

    const subject = encodeURIComponent(__('Help with order :number', { number: orderNumber }));

    return (
        <a
            href={`mailto:${email}?subject=${subject}`}
            className="flex items-center justify-center gap-2 font-semibold text-muted transition-colors can-hover:hover:text-primary"
        >
            <MessageSquareIcon size={15} strokeWidth={1.8} aria-hidden="true" />
            {__('Need help with this order?')}
        </a>
    );
}
