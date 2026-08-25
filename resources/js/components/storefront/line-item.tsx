import { Link } from '@inertiajs/react';
import type { ComponentProps, ReactNode } from 'react';

import { ContainedMedia } from '@/components/storefront/contained-media';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { MediaItem } from '@/types/media';

interface LineItemProps {
    title: string;
    variantTitle?: string | null;
    url: ComponentProps<typeof Link>['href'] | null;
    media: MediaItem | null;
    quantity: number;
    price: string;
    currencyCode?: string;
    className?: string;
    leading?: ReactNode;
    labelFor?: string;
    note?: ReactNode;
    children?: ReactNode;
}

const thumbClass = 'border-line bg-surface-2 relative block size-16 shrink-0 overflow-hidden rounded-sm border';
const titleClass = 'text-ink line-clamp-2 font-semibold leading-snug';

export function LineItem({
    title,
    variantTitle,
    url,
    media,
    quantity,
    price,
    currencyCode,
    className,
    leading,
    labelFor,
    note,
    children,
}: LineItemProps) {
    const { formatMoney } = useFormatMoney();

    const row = (
        <>
            {leading}
            {url ? (
                <Link href={url} tabIndex={-1} aria-hidden="true" className={thumbClass}>
                    <ContainedMedia media={media} source="small" />
                </Link>
            ) : (
                <span className={thumbClass}>
                    <ContainedMedia media={media} source="small" />
                </span>
            )}

            <div className="min-w-0 flex-1">
                {url ? (
                    <Link href={url} className={cn(titleClass, 'transition-colors hover:text-primary')}>
                        {title}
                    </Link>
                ) : (
                    <span className={titleClass}>{title}</span>
                )}
                {variantTitle && <div className="mt-0.5 truncate text-sm text-muted">{variantTitle}</div>}
                <div className="mt-0.5 text-sm text-muted">{__('Qty :count', { count: String(quantity) })}</div>
                {note && <div className="mt-0.5 text-sm text-muted">{note}</div>}
            </div>

            <span className="shrink-0 font-bold text-ink">{formatMoney(price, currencyCode)}</span>
        </>
    );

    return (
        <li className={cn(className)}>
            {labelFor ? (
                <label htmlFor={labelFor} className="flex cursor-pointer items-start gap-3.5">
                    {row}
                </label>
            ) : (
                <div className="flex items-start gap-3.5">{row}</div>
            )}

            {children}
        </li>
    );
}
