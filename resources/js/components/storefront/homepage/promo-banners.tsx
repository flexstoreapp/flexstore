import { Link } from '@inertiajs/react';

import { imageZoom, MediaImage } from '@/components/storefront/media-image';
import { Section } from '@/components/storefront/section';
import { overlayPosition } from '@/components/storefront/section-text-align';
import { overlayTextClass } from '@/components/storefront/section-text-color';
import { cn, getTranslation } from '@/lib/utils';
import type { PromoBannersSectionData } from '@/types';

export function PromoBannersSection({ section }: { section: PromoBannersSectionData }) {
    const banners = section.settings.banners;

    if (banners.length === 0) {
        return null;
    }

    return (
        <Section className="mt-8 grid grid-cols-1 gap-5 sm:mt-12 md:grid-cols-2">
            {banners.map((banner, index) => {
                const title = getTranslation(banner.title);
                const subtitle = getTranslation(banner.subtitle);
                const position = overlayPosition(banner.text_align);

                const content = (
                    <>
                        <MediaImage
                            media={banner.image}
                            className={cn(imageZoom, 'absolute inset-0')}
                            placeholderClassName="absolute inset-0"
                        />
                        <span
                            className={cn(
                                'pointer-events-none absolute inset-0 flex flex-col p-4 sm:p-10 md:p-6 lg:p-10',
                                position.justify,
                                position.items,
                            )}
                        >
                            <span className={cn('block max-w-[62%] sm:max-w-none', position.text)}>
                                {title && (
                                    <span
                                        className={cn(
                                            'block font-head text-xl leading-[1.08] font-bold sm:text-5xl sm:leading-tight md:text-3xl lg:text-4xl xl:text-5xl',
                                            overlayTextClass(banner.text_color, 'heading'),
                                        )}
                                    >
                                        {title}
                                    </span>
                                )}
                                {subtitle && (
                                    <span
                                        className={cn(
                                            'mt-2 block text-xs sm:mt-1.5 sm:text-base',
                                            overlayTextClass(banner.text_color, 'body'),
                                        )}
                                    >
                                        {subtitle}
                                    </span>
                                )}
                            </span>
                        </span>
                    </>
                );

                const className = 'group relative h-[220px] overflow-hidden rounded-md';

                return banner.url ? (
                    <Link key={index} href={banner.url} className={className}>
                        {content}
                    </Link>
                ) : (
                    <div key={index} className={className}>
                        {content}
                    </div>
                );
            })}
        </Section>
    );
}
