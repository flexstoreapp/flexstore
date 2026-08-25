import { MaximizeIcon } from 'lucide-react';
import { type KeyboardEvent, useRef, useState } from 'react';

import { ContainedMedia } from '@/components/storefront/contained-media';
import { ProductLightbox } from '@/components/storefront/product/product-lightbox';
import { ProductBadge, type ProductBadgeData } from '@/components/storefront/product-badge';
import { useDirection } from '@/hooks/storefront/use-direction';
import { __ } from '@/lib/i18n';
import { mediaBoxRatio } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { TranslatableField } from '@/types';
import type { MediaItem } from '@/types/media';

interface ProductGalleryProps {
    media: MediaItem[];
    title: TranslatableField;
    badge: ProductBadgeData;
    activeMediaId?: number | null;
}

export function ProductGallery({ media, title, badge, activeMediaId = null }: ProductGalleryProps) {
    const [activeIndex, setActiveIndex] = useState(() =>
        Math.max(
            0,
            media.findIndex((item) => item.id === activeMediaId),
        ),
    );
    const [syncedMediaId, setSyncedMediaId] = useState(activeMediaId);
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const tabRefs = useRef<(HTMLButtonElement | null)[]>([]);
    const direction = useDirection();
    const altText = getTranslation(title);
    const hasThumbnails = media.length > 1;

    if (activeMediaId !== syncedMediaId) {
        setSyncedMediaId(activeMediaId);
        const index = media.findIndex((item) => item.id === activeMediaId);
        if (index >= 0) {
            setActiveIndex(index);
        }
    }

    const safeIndex = Math.min(activeIndex, Math.max(0, media.length - 1));
    const active = media[safeIndex];
    const ratio = mediaBoxRatio(media);

    const onTabsKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
        const forward = direction === 'rtl' ? -1 : 1;
        const count = media.length;

        let next = -1;
        if (event.key === 'ArrowRight') next = (safeIndex + forward + count) % count;
        else if (event.key === 'ArrowLeft') next = (safeIndex - forward + count) % count;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = count - 1;

        if (next < 0) {
            return;
        }

        event.preventDefault();
        setActiveIndex(next);
        tabRefs.current[next]?.focus();
    };

    return (
        <div
            className="flex w-full flex-col gap-4"
            role="group"
            aria-roledescription="carousel"
            aria-label={__('Product images')}
        >
            <div className="relative overflow-hidden rounded-md bg-surface-2" style={{ aspectRatio: ratio }}>
                {hasThumbnails ? (
                    <div aria-live="polite" className="size-full">
                        {media.map((item, index) => (
                            <div
                                key={item.id}
                                role="tabpanel"
                                id={`gal-slide-${item.id}`}
                                aria-labelledby={`gal-tab-${item.id}`}
                                hidden={index !== safeIndex}
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
                    <ContainedMedia media={active} source="full" alt={active?.alt ?? altText} loading="eager" />
                )}

                <ProductBadge badge={badge} size="lg" />

                {active && (
                    <button
                        type="button"
                        aria-label={__('Zoom image')}
                        onClick={() => setLightboxOpen(true)}
                        className="absolute end-4 bottom-4 flex size-11 items-center justify-center rounded-full border border-line bg-surface/90 text-ink shadow-sm backdrop-blur transition focus-visible:outline-offset-2 can-hover:hover:text-primary"
                    >
                        <MaximizeIcon size={18} aria-hidden="true" />
                    </button>
                )}
            </div>

            {hasThumbnails && (
                <div
                    className="-mx-0.5 -my-1.5 no-scrollbar flex gap-3 overflow-x-auto px-0.5 py-1.5"
                    role="tablist"
                    aria-label={__('Choose image')}
                    onKeyDown={onTabsKeyDown}
                >
                    {media.map((item, index) => {
                        const isActive = index === safeIndex;
                        return (
                            <button
                                key={item.id}
                                ref={(el) => {
                                    tabRefs.current[index] = el;
                                }}
                                type="button"
                                role="tab"
                                id={`gal-tab-${item.id}`}
                                aria-controls={`gal-slide-${item.id}`}
                                aria-selected={isActive}
                                tabIndex={isActive ? 0 : -1}
                                onClick={() => setActiveIndex(index)}
                                style={{ aspectRatio: ratio }}
                                className="relative max-w-28 shrink-0 grow basis-22 overflow-hidden rounded-sm border border-line bg-surface-2 transition focus-visible:outline-offset-0 aria-selected:border-primary can-hover:hover:border-primary"
                            >
                                <ContainedMedia media={item} />
                            </button>
                        );
                    })}
                </div>
            )}

            <ProductLightbox
                open={lightboxOpen}
                media={media}
                index={safeIndex}
                title={title}
                onClose={() => setLightboxOpen(false)}
                onIndexChange={setActiveIndex}
            />
        </div>
    );
}
