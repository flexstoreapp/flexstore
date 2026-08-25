import { Link } from '@inertiajs/react';

import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { ContainedMedia } from '@/components/storefront/contained-media';
import { imageZoom } from '@/components/storefront/media-image';
import { ProductPrice } from '@/components/storefront/product-price';
import { SectionFrame } from '@/components/storefront/section-frame';
import { Section, SectionTitle } from '@/components/storefront/section-shell';
import { mediaBoxRatio } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type { FeaturedProductsSectionData, ProductData } from '@/types';

function CompactProductCard({ product, ratio }: { product: ProductData; ratio: number }) {
    const brand = getTranslation(product.brand?.name);
    const title = getTranslation(product.title);

    return (
        <Link
            href={ProductController.show(product.url_handle)}
            className="group relative flex items-center gap-5 border-e border-b border-line p-6 focus-visible:outline-none"
        >
            <span className="pointer-events-none absolute inset-0 z-20 border-2 border-primary opacity-0 group-focus-visible:opacity-100" />
            <span
                style={{ aspectRatio: ratio }}
                className="relative block w-[110px] shrink-0 self-center overflow-hidden rounded-sm bg-surface-2"
            >
                <ContainedMedia media={product.featured_media} className={imageZoom} />
            </span>
            <span className="block min-w-0">
                {brand && (
                    <span className="block text-2xs font-semibold tracking-label text-muted uppercase">{brand}</span>
                )}
                <span className="mt-1 line-clamp-2 text-lg leading-snug font-semibold text-ink transition group-hover:text-primary">
                    {title}
                </span>
                <span className="mt-2 block">
                    <ProductPrice product={product} size="md" />
                </span>
            </span>
        </Link>
    );
}

export function FeaturedProductsSection({ section }: { section: FeaturedProductsSectionData }) {
    const products = section.settings.products;

    if (products.length === 0) {
        return null;
    }

    const title = getTranslation(section.title);
    const ratio = mediaBoxRatio(products.map((product) => product.featured_media));

    return (
        <Section>
            <div className="mb-6">
                <SectionTitle>{title}</SectionTitle>
            </div>
            <SectionFrame cols="grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {products.map((product) => (
                    <CompactProductCard key={product.id} product={product} ratio={ratio} />
                ))}
            </SectionFrame>
        </Section>
    );
}
