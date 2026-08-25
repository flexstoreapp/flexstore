import * as BrandController from '@/actions/App/Http/Controllers/Storefront/BrandController';
import { ArrowLink } from '@/components/storefront/arrow-link';
import { BrandTile } from '@/components/storefront/brand-tile';
import { SectionFrame } from '@/components/storefront/section-frame';
import { SectionHeader } from '@/components/storefront/section-header';
import { Section } from '@/components/storefront/section-shell';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { BrandStripSectionData } from '@/types';

export function BrandStripSection({ section }: { section: BrandStripSectionData }) {
    const { brands = [], grayscale } = section.settings;
    const title = getTranslation(section.title);

    if (brands.length === 0) {
        return null;
    }

    return (
        <Section>
            <SectionHeader
                title={title}
                action={<ArrowLink href={BrandController.index()}>{__('View all brands')}</ArrowLink>}
            />

            <SectionFrame cols="grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                {brands.map((brand) => (
                    <BrandTile
                        key={brand.id}
                        name={brand.name}
                        urlHandle={brand.url_handle}
                        image={brand.image}
                        grayscale={grayscale}
                    />
                ))}
            </SectionFrame>
        </Section>
    );
}
