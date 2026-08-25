import { Deferred, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { ProductBreadcrumb } from '@/components/storefront/product/product-breadcrumb';
import { ProductGallery } from '@/components/storefront/product/product-gallery';
import { ProductInfo } from '@/components/storefront/product/product-info';
import { ProductTabs } from '@/components/storefront/product/product-tabs';
import { RelatedProducts, RelatedProductsSkeleton } from '@/components/storefront/product/related-products';
import { resolveGalleryBadge } from '@/components/storefront/product-badge';
import { Section } from '@/components/storefront/section';
import { useProductPurchase } from '@/hooks/storefront/use-product-purchase';
import { useTrackProductView } from '@/hooks/storefront/use-track-product-view';
import { useVariantUrlSync } from '@/hooks/storefront/use-variant-url-sync';
import { galleryMedia, mediaBoxRatio } from '@/lib/media';
import { getTranslation } from '@/lib/utils';
import type {
    ProductData,
    ProductDetailData,
    ProductDetailSettings,
    ReviewData,
    SimplePaginated,
    StorefrontSharedData,
} from '@/types';

interface ProductShowProps {
    product: ProductDetailData;
    settings: ProductDetailSettings;
    canReview: boolean;
    reviews?: SimplePaginated<ReviewData>;
    relatedProducts?: ProductData[];
}

export default function ProductShow({ product, settings, canReview, reviews, relatedProducts }: ProductShowProps) {
    const { activeCurrency } = usePage<StorefrontSharedData>().props;
    const purchase = useProductPurchase(product, () => {});
    const { resolvedVariant, pricing } = purchase;

    const [activeTab, setActiveTab] = useState('');
    const goToReviews = () => {
        setActiveTab('reviews');
        document.getElementById('reviews')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const title = getTranslation(product.title, 'Product');

    const media = galleryMedia(product.media, resolvedVariant?.media);

    const badge = resolveGalleryBadge(resolvedVariant ? resolvedVariant.in_stock : product.in_stock, false, pricing);

    useVariantUrlSync(product, resolvedVariant, purchase.selectVariant);
    useTrackProductView(product, pricing.price, activeCurrency, resolvedVariant);

    return (
        <>
            <ProductBreadcrumb category={product.category} title={title} />

            <Section className="mt-6 pb-12">
                <div className="rounded-md border border-line bg-surface p-5 sm:p-8 md:p-10">
                    <div className="grid grid-cols-1 items-start gap-8 md:grid-cols-2 md:gap-10 lg:grid-cols-[5fr_6fr] lg:gap-14">
                        <div className="lg:sticky lg:top-24 lg:self-start">
                            <ProductGallery
                                media={media}
                                title={product.title}
                                badge={badge}
                                activeMediaId={resolvedVariant?.media?.id ?? null}
                            />
                        </div>
                        <ProductInfo
                            product={product}
                            settings={settings}
                            purchase={purchase}
                            onReviewsClick={goToReviews}
                        />
                    </div>

                    <ProductTabs
                        product={product}
                        settings={settings}
                        reviews={reviews}
                        canReview={canReview}
                        active={activeTab}
                        onActiveChange={setActiveTab}
                    />
                </div>

                {settings.show_related_products && (
                    <div className="mt-6">
                        <Deferred
                            data="relatedProducts"
                            fallback={<RelatedProductsSkeleton ratio={mediaBoxRatio(media)} />}
                        >
                            <RelatedProducts products={relatedProducts} />
                        </Deferred>
                    </div>
                )}
            </Section>
        </>
    );
}
