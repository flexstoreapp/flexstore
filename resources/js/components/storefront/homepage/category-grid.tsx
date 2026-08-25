import { Link } from '@inertiajs/react';

import * as CategoryController from '@/actions/App/Http/Controllers/Storefront/CategoryController';
import * as CategoryProductController from '@/actions/App/Http/Controllers/Storefront/CategoryProductController';
import { ArrowLink } from '@/components/storefront/arrow-link';
import { imageZoom, MediaImage } from '@/components/storefront/media-image';
import { SectionFrame } from '@/components/storefront/section-frame';
import { SectionHeader } from '@/components/storefront/section-header';
import { Section } from '@/components/storefront/section-shell';
import { overlayTextClass } from '@/components/storefront/section-text-color';
import { __ } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { CategoryGridSectionData } from '@/types';

export function CategoryGridSection({ section }: { section: CategoryGridSectionData }) {
    const { categories } = section.settings;
    const title = getTranslation(section.title);

    if (categories.length === 0) {
        return null;
    }

    return (
        <Section>
            <SectionHeader
                title={title}
                action={<ArrowLink href={CategoryController.index()}>{__('View all categories')}</ArrowLink>}
            />

            <SectionFrame cols="grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                {categories.map((category) => {
                    const name = getTranslation(category.name);
                    const className =
                        'group relative block aspect-[3/2] overflow-hidden border-e border-b border-line bg-surface-2 focus-visible:outline-none';
                    const inner = (
                        <>
                            <MediaImage
                                media={category.image}
                                className={cn(imageZoom, 'absolute inset-0 object-top')}
                                placeholderClassName="absolute inset-0"
                            />
                            <span className="pointer-events-none absolute inset-0 z-20 border-2 border-primary opacity-0 group-focus-visible:opacity-100" />
                            <span
                                className={cn(
                                    'absolute inset-x-0 bottom-0 z-10 p-4 text-base leading-snug font-semibold transition group-hover:text-primary sm:p-5 sm:text-lg',
                                    overlayTextClass(category.text_color, 'heading'),
                                )}
                            >
                                {name}
                            </span>
                        </>
                    );

                    return category.url_handle ? (
                        <Link
                            key={category.category_id}
                            href={CategoryProductController.show(category.url_handle)}
                            className={className}
                        >
                            {inner}
                        </Link>
                    ) : (
                        <div key={category.category_id} className={className}>
                            {inner}
                        </div>
                    );
                })}
            </SectionFrame>
        </Section>
    );
}
