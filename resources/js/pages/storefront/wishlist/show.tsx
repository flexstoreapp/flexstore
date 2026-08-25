import { usePage } from '@inertiajs/react';
import { HeartIcon } from 'lucide-react';

import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { PageHeader } from '@/components/storefront/page-header';
import { ProductGrid } from '@/components/storefront/product-grid';
import { Section } from '@/components/storefront/section';
import { StatusPanel } from '@/components/storefront/status-panel';
import { WishlistEmptyState } from '@/components/storefront/wishlist-empty-state';
import { __, transChoice } from '@/lib/i18n';
import type { ProductData, StorefrontSharedData } from '@/types';

interface WishlistShowProps {
    products: ProductData[];
    is_shared: boolean;
}

export default function WishlistShow({ products, is_shared: isShared }: WishlistShowProps) {
    const { wishlist } = usePage<StorefrontSharedData>().props;
    const visibleProducts = isShared
        ? products
        : products.filter((product) => wishlist.product_ids.includes(product.id));
    const hasItems = visibleProducts.length > 0;
    const savedLabel = transChoice(':count item saved|:count items saved', visibleProducts.length);
    const heading = isShared ? __('Shared wishlist') : __('Wishlist');

    return (
        <>
            <PageHeader
                crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: heading }]}
                heading={hasItems ? heading : undefined}
                subheading={hasItems ? savedLabel : undefined}
            />

            {hasItems ? (
                <Section className="mt-6 pb-12">
                    <ProductGrid products={visibleProducts} />
                </Section>
            ) : isShared ? (
                <Section className="mt-6 pb-12">
                    <StatusPanel
                        icon={<HeartIcon size={34} strokeWidth={1.6} aria-hidden="true" />}
                        title={__('This shared wishlist is empty')}
                        description={__('There are no items to show in this wishlist.')}
                    />
                </Section>
            ) : (
                <WishlistEmptyState />
            )}
        </>
    );
}
