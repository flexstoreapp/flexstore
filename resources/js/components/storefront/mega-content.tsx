import { Link } from '@inertiajs/react';

import { ArrowLink } from '@/components/storefront/arrow-link';
import { useImageFallback } from '@/hooks/storefront/use-image-fallback';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontMenuFeatured, StorefrontMenuItem } from '@/types';

export interface MegaContentSection {
    heading: string;
    href: string;
    items: { label: string; href: string }[];
}

export const toMegaSections = (item: StorefrontMenuItem): MegaContentSection[] =>
    (item.children ?? []).map((column) => ({
        heading: column.label,
        href: column.url,
        items: (column.children ?? []).map((leaf) => ({ label: leaf.label, href: leaf.url })),
    }));

interface MegaContentProps {
    sections: MegaContentSection[];
    featured?: StorefrontMenuFeatured | null;
    footerHref: string;
    columnsClassName?: string;
}

export function MegaContent({ sections, featured, footerHref, columnsClassName }: MegaContentProps) {
    return (
        <>
            <div className="mx-auto flex w-full max-w-page gap-x-8 px-6 pt-6 pb-5">
                <div className={cn('grid grow gap-x-8 gap-y-6', columnsClassName ?? 'grid-cols-3')}>
                    {sections.map((section) => (
                        <div key={section.heading} className="min-w-0">
                            <Link
                                href={section.href}
                                className="mb-2 block border-b border-line pb-2.5 text-2xs font-bold tracking-label text-ink uppercase transition-colors can-hover:hover:text-primary"
                            >
                                {section.heading}
                            </Link>
                            <div className="flex flex-col">
                                {section.items.map((item) => (
                                    <Link
                                        key={item.label}
                                        href={item.href}
                                        className="py-1.5 text-sm font-normal text-body transition-colors can-hover:hover:text-primary"
                                    >
                                        {item.label}
                                    </Link>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>

                {featured && <MegaFeatured featured={featured} />}
            </div>
            <div className="border-t border-line bg-surface-2">
                <div className="mx-auto flex h-12 w-full max-w-page items-center justify-end px-6">
                    <ArrowLink href={footerHref}>{__('View all')}</ArrowLink>
                </div>
            </div>
        </>
    );
}

function MegaFeatured({ featured }: { featured: StorefrontMenuFeatured }) {
    const { failed, imgProps } = useImageFallback(featured.image);

    const content = (
        <>
            <span className="aspect-square overflow-hidden rounded-md bg-surface-2">
                {!failed && (
                    <img
                        {...imgProps}
                        src={featured.image ?? undefined}
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="h-full w-full object-cover"
                    />
                )}
            </span>
            {featured.title && (
                <span className="mt-3.5 block text-sm leading-snug font-semibold text-ink">{featured.title}</span>
            )}
        </>
    );

    if (featured.url) {
        return (
            <Link href={featured.url} className="flex w-52 shrink-0 flex-col self-start">
                {content}
            </Link>
        );
    }

    return <div className="flex w-52 shrink-0 flex-col self-start">{content}</div>;
}
