import { Link, usePage } from '@inertiajs/react';
import { HeartIcon } from 'lucide-react';

import * as BrandProductController from '@/actions/App/Http/Controllers/Storefront/BrandProductController';
import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { ContainedMedia } from '@/components/storefront/contained-media';
import { imageZoom } from '@/components/storefront/media-image';
import { resolveCardBadge, ProductBadge } from '@/components/storefront/product-badge';
import { ProductCardCartBar } from '@/components/storefront/product-card-cart-bar';
import { ProductCardQuickActions } from '@/components/storefront/product-card-quick-actions';
import { ProductPrice } from '@/components/storefront/product-price';
import { useQuickView } from '@/components/storefront/quick-view-context';
import { useAddToCart } from '@/hooks/storefront/use-add-to-cart';
import { useWishlist } from '@/hooks/storefront/use-product-lists';
import { analytics } from '@/lib/analytics';
import { mediaBoxRatio } from '@/lib/media';
import { cn, getTranslation } from '@/lib/utils';
import type { ProductData, StorefrontSharedData } from '@/types';

interface ProductCardProps {
    product: ProductData;
    layout?: 'grid' | 'carousel' | 'compare';
    ratio?: number;
    onQuickView?: () => void;
    as?: 'div' | 'li' | 'th';
}

const borderByLayout: Record<NonNullable<ProductCardProps['layout']>, string> = {
    grid: 'border-b border-e border-line',
    carousel: 'border-e border-line',
    compare: '',
};

const REVEAL_ON_HOVER =
    'group-hover:translate-y-0 group-hover:opacity-100 group-has-[:focus-visible]:translate-y-0 group-has-[:focus-visible]:opacity-100';

export function ProductCard({ product, layout = 'grid', ratio, onQuickView, as = 'div' }: ProductCardProps) {
    const Root = as;
    const quickView = useQuickView();
    const handleQuickView = onQuickView ?? (() => quickView.open(product.url_handle));

    const { activeCurrency } = usePage<StorefrontSharedData>().props;
    const wishlist = useWishlist(product.id, () => analytics.addToWishlist(product, product.price, activeCurrency));
    const cart = useAddToCart();
    const addToCart = () =>
        cart.addToCart(
            { productId: product.id },
            {
                resetSuccessAfter: 1500,
                resetErrorAfter: 3000,
                onSuccess: () => analytics.addToCart(product, product.price, 1, activeCurrency),
            },
        );
    const selectItem = () => analytics.selectItem(product, activeCurrency);

    const productHref = ProductController.show(product.url_handle);
    const title = getTranslation(product.title);
    const brand = getTranslation(product.brand?.name);
    const brandHref = product.brand ? BrandProductController.show(product.brand.url_handle) : null;

    const variant = product.has_variants ? 'select-options' : product.in_stock ? 'in-stock' : 'out-of-stock';
    const reveal = cart.status !== 'idle' ? 'translate-y-0 opacity-100' : REVEAL_ON_HOVER;

    return (
        <Root className={cn('group relative flex h-full flex-col p-5', borderByLayout[layout])}>
            <span className="pointer-events-none absolute inset-0 z-20 border-2 border-primary opacity-0 group-has-[a[aria-label]:focus-visible]:opacity-100" />

            <div className="relative -mx-5 -mt-5 mb-4 overflow-hidden border-b border-line">
                <Link
                    href={productHref}
                    onClick={selectItem}
                    className="relative block overflow-hidden bg-surface-2 focus-visible:outline-none"
                    style={{ aspectRatio: ratio ?? mediaBoxRatio([product.featured_media]) }}
                    aria-label={title}
                >
                    <ContainedMedia media={product.featured_media} className={imageZoom} />
                </Link>

                <ProductBadge badge={resolveCardBadge(product, variant === 'out-of-stock')} size="sm" />

                <ProductCardQuickActions reveal={reveal} onQuickView={handleQuickView} />

                <ProductCardCartBar
                    variant={variant}
                    reveal={reveal}
                    cart={cart}
                    onAddToCart={addToCart}
                    onQuickView={handleQuickView}
                />
            </div>

            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    {brand && brandHref && (
                        <Link
                            href={brandHref}
                            className="block truncate text-2xs font-semibold tracking-label text-muted uppercase transition-colors hover:text-primary"
                        >
                            {brand}
                        </Link>
                    )}
                    <Link
                        href={productHref}
                        onClick={selectItem}
                        className="mt-1 line-clamp-2 leading-snug font-semibold text-ink transition-colors hover:text-primary"
                    >
                        {title}
                    </Link>
                </div>
                <button
                    type="button"
                    onClick={wishlist.toggle}
                    aria-label={wishlist.active ? `Remove ${title} from wishlist` : `Add ${title} to wishlist`}
                    className={cn(
                        '-m-1 shrink-0 p-1 transition-colors hover:text-primary',
                        wishlist.active ? 'text-primary' : 'text-muted',
                    )}
                >
                    <HeartIcon
                        size={18}
                        strokeWidth={1.8}
                        fill={wishlist.active ? 'currentColor' : 'none'}
                        aria-hidden="true"
                    />
                </button>
            </div>

            <ProductPrice product={product} size="md" className="mt-2 block" />
        </Root>
    );
}
