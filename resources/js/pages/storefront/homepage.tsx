import { BrandStripSection } from '@/components/storefront/homepage/brand-strip';
import { CategoryGridSection } from '@/components/storefront/homepage/category-grid';
import { FeaturedProductsSection } from '@/components/storefront/homepage/featured-products';
import { HeroSliderSection } from '@/components/storefront/homepage/hero-slider';
import { InfoStripSection } from '@/components/storefront/homepage/info-strip';
import { ProductCarouselSection } from '@/components/storefront/homepage/product-carousel';
import { ProductColumnsSection } from '@/components/storefront/homepage/product-columns';
import { ProductTabsSection } from '@/components/storefront/homepage/product-tabs';
import { PromoBannersSection } from '@/components/storefront/homepage/promo-banners';
import { TestimonialsSection } from '@/components/storefront/homepage/testimonials';
import { HomepageEmptyState } from '@/components/storefront/homepage-empty-state';
import type { HomepageSection } from '@/types';

interface HomepageProps {
    sections: HomepageSection[];
}

function renderSection(section: HomepageSection, previous?: HomepageSection) {
    switch (section.type) {
        case 'hero_slider':
            return <HeroSliderSection key={section.id} section={section} />;
        case 'info_strip':
            return <InfoStripSection key={section.id} section={section} tight={previous?.type === 'hero_slider'} />;
        case 'category_grid':
            return <CategoryGridSection key={section.id} section={section} />;
        case 'featured_products':
            return <FeaturedProductsSection key={section.id} section={section} />;
        case 'product_tabs':
            return <ProductTabsSection key={section.id} section={section} />;
        case 'promo_banners':
            return <PromoBannersSection key={section.id} section={section} />;
        case 'product_carousel':
            return <ProductCarouselSection key={section.id} section={section} />;
        case 'product_columns':
            return <ProductColumnsSection key={section.id} section={section} />;
        case 'brand_strip':
            return <BrandStripSection key={section.id} section={section} />;
        case 'testimonials':
            return <TestimonialsSection key={section.id} section={section} />;
        default:
            return null;
    }
}

export default function Homepage({ sections }: HomepageProps) {
    return (
        <>
            {sections.length === 0 ? (
                <HomepageEmptyState />
            ) : (
                <div className="pb-12">
                    {sections.map((section, index) => renderSection(section, sections[index - 1]))}
                </div>
            )}
        </>
    );
}
