import { ExternalLinkIcon } from 'lucide-react';

import { __nodes } from '@/lib/i18n';

interface TrackingLinkProps {
    trackingNumber: string;
    trackingUrl?: string | null;
}

export function TrackingLink({ trackingNumber, trackingUrl }: TrackingLinkProps) {
    return (
        <div className="text-sm text-muted-foreground">
            {__nodes('Tracking: :number', {
                number: trackingUrl ? (
                    <a
                        href={trackingUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 text-xs text-foreground underline underline-offset-4"
                    >
                        <bdi>{trackingNumber}</bdi>
                        <ExternalLinkIcon className="size-3.5 rtl:-scale-x-100" />
                    </a>
                ) : (
                    <bdi className="text-xs text-foreground">{trackingNumber}</bdi>
                ),
            })}
        </div>
    );
}
