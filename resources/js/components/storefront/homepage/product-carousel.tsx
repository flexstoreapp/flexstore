import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { ProductCard } from '@/components/storefront/product-card';
import { Section, SectionTitle } from '@/components/storefront/section-shell';
import { useDirection } from '@/hooks/storefront/use-direction';
import { __ } from '@/lib/i18n';
import { mediaBoxRatio } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { ProductCarouselSectionData } from '@/types';

export function ProductCarouselSection({ section }: { section: ProductCarouselSectionData }) {
    const trackRef = useRef<HTMLDivElement>(null);
    const [canScrollStart, setCanScrollStart] = useState(false);
    const [canScrollEnd, setCanScrollEnd] = useState(false);
    const dir = useDirection();
    const products = section.settings.products;
    const ratio = mediaBoxRatio(products.map((product) => product.featured_media));

    useEffect(() => {
        const track = trackRef.current;
        if (!track) {
            return;
        }

        const update = () => {
            const maxScroll = track.scrollWidth - track.clientWidth;
            const scrolled = Math.abs(track.scrollLeft);
            setCanScrollStart(scrolled > 1);
            setCanScrollEnd(scrolled < maxScroll - 1);
        };

        update();
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);

        return () => {
            track.removeEventListener('scroll', update);
            window.removeEventListener('resize', update);
        };
    }, [products.length]);

    if (products.length === 0) {
        return null;
    }

    const title = getTranslation(section.title);

    const scrollBy = (direction: 1 | -1) => {
        const track = trackRef.current;
        if (!track) {
            return;
        }

        track.scrollBy({ left: (dir === 'rtl' ? -direction : direction) * track.clientWidth, behavior: 'smooth' });
    };

    const navButtonClass =
        'border-line-strong flex h-10 w-10 items-center justify-center rounded-md border transition-colors can-hover:hover:border-primary can-hover:hover:text-primary disabled:pointer-events-none disabled:opacity-40';

    return (
        <Section>
            <div className="mb-6 flex items-center justify-between gap-4">
                <SectionTitle>{title}</SectionTitle>
                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => scrollBy(-1)}
                        disabled={!canScrollStart}
                        aria-label={__('Previous products')}
                        className={navButtonClass}
                    >
                        <ChevronLeftIcon size={16} strokeWidth={2} aria-hidden="true" className="rtl:-scale-x-100" />
                    </button>
                    <button
                        type="button"
                        onClick={() => scrollBy(1)}
                        disabled={!canScrollEnd}
                        aria-label={__('Next products')}
                        className={navButtonClass}
                    >
                        <ChevronRightIcon size={16} strokeWidth={2} aria-hidden="true" className="rtl:-scale-x-100" />
                    </button>
                </div>
            </div>

            <div className="relative after:pointer-events-none after:absolute after:inset-0 after:z-10 after:rounded-md after:border after:border-line">
                <div
                    ref={trackRef}
                    className="no-scrollbar flex snap-x snap-mandatory overflow-x-auto rounded-md bg-surface"
                >
                    {products.map((product) => (
                        <div key={product.id} className="w-1/2 shrink-0 snap-start md:w-1/3 lg:w-1/4 xl:w-1/5">
                            <ProductCard product={product} layout="carousel" ratio={ratio} />
                        </div>
                    ))}
                </div>
            </div>
        </Section>
    );
}
