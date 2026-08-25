import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { BrandTile } from '@/components/storefront/brand-tile';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { SectionFrame } from '@/components/storefront/section-frame';
import { __ } from '@/lib/i18n';
import type { BrandListItem } from '@/types';

interface BrandListProps {
    brands: BrandListItem[];
}

export default function BrandList({ brands }: BrandListProps) {
    return (
        <>
            <PageHeader
                crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: __('Brands') }]}
                heading={__('All brands')}
            />

            <Section className="mt-6 pb-12" aria-label={__('Brands')}>
                <SectionFrame cols="grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    {brands.map((brand) => (
                        <BrandTile key={brand.id} name={brand.name} urlHandle={brand.url_handle} image={brand.image} />
                    ))}
                </SectionFrame>
            </Section>
        </>
    );
}
