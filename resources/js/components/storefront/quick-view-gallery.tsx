import { type KeyboardEvent, useRef, useState } from 'react';

import { ContainedMedia } from '@/components/storefront/contained-media';
import { ProductBadge, type ProductBadgeData } from '@/components/storefront/product-badge';
import { useDirection } from '@/hooks/storefront/use-direction';
import { __ } from '@/lib/i18n';
import { mediaBoxRatio } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { TranslatableField } from '@/types';
import type { MediaItem } from '@/types/media';

interface QuickViewGalleryProps {
    media: MediaItem[];
    title: TranslatableField;
    badge: ProductBadgeData;
    activeMediaId?: number | null;
}

export function QuickViewGallery({ media, title, badge, activeMediaId = null }: QuickViewGalleryProps) {
    const [activeMedia, setActiveMedia] = useState<MediaItem | null>(
        () => media.find((item) => item.id === activeMediaId) ?? media[0] ?? null,
    );
    const [syncedMediaId, setSyncedMediaId] = useState(activeMediaId);
    const tabRefs = useRef<(HTMLButtonElement | null)[]>([]);
    const direction = useDirection();
    const altText = getTranslation(title);
    const hasThumbnails = media.length > 1;
    const ratio = mediaBoxRatio(media);

    if (activeMediaId !== syncedMediaId) {
        setSyncedMediaId(activeMediaId);
        const match = media.find((item) => item.id === activeMediaId);
        if (match) {
            setActiveMedia(match);
        }
    }

    const onTabsKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        const index = media.findIndex((item) => item.id === activeMedia?.id);
        if (index < 0) {
            return;
        }

        const forward = direction === 'rtl' ? -1 : 1;
        const count = media.length;

        let next = -1;
        if (event.key === 'ArrowRight') next = (index + forward + count) % count;
        else if (event.key === 'ArrowLeft') next = (index - forward + count) % count;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = count - 1;

        if (next < 0) {
            return;
        }

        event.preventDefault();
        setActiveMedia(media[next]);
        tabRefs.current[next]?.focus();
    };

    return (
        <div className="flex flex-col gap-3 p-5 pt-16 sm:p-6 lg:pt-6">
            <div className="relative overflow-hidden rounded-sm bg-surface-2" style={{ aspectRatio: ratio }}>
                {hasThumbnails ? (
                    <div aria-live="polite" className="size-full">
                        {media.map((item, index) => (
                            <div
                                key={item.id}
                                role="tabpanel"
                                id={`qv-slide-${item.id}`}
                                aria-labelledby={`qv-tab-${item.id}`}
                                hidden={activeMedia?.id !== item.id}
                                className="size-full"
                            >
                                <ContainedMedia
                                    media={item}
                                    source="full"
                                    alt={item.alt ?? altText}
                                    loading={index === 0 ? 'eager' : 'lazy'}
                                />
                            </div>
                        ))}
                    </div>
                ) : (
                    <ContainedMedia
                        media={activeMedia}
                        source="full"
                        alt={activeMedia?.alt ?? altText}
                        loading="eager"
                    />
                )}

                <ProductBadge badge={badge} size="lg" />
            </div>
            {hasThumbnails && (
                <div
                    className="-mx-0.5 -my-1.5 no-scrollbar flex gap-3 overflow-x-auto px-0.5 py-1.5"
                    role="tablist"
                    aria-label={__('Choose image')}
                    onKeyDown={onTabsKeyDown}
                >
                    {media.map((item, index) => {
                        const isActive = activeMedia?.id === item.id;
                        return (
                            <button
                                key={item.id}
                                ref={(el) => {
                                    tabRefs.current[index] = el;
                                }}
                                type="button"
                                role="tab"
                                id={`qv-tab-${item.id}`}
                                aria-controls={`qv-slide-${item.id}`}
                                aria-selected={isActive}
                                tabIndex={isActive ? 0 : -1}
                                onClick={() => setActiveMedia(item)}
                                style={{ aspectRatio: ratio }}
                                className="relative max-w-28 shrink-0 grow basis-22 overflow-hidden rounded-sm border border-line bg-surface-2 transition focus-visible:outline-offset-0 aria-selected:border-primary can-hover:hover:border-primary"
                            >
                                <ContainedMedia media={item} />
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
