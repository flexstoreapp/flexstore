import { XIcon } from 'lucide-react';
import type { RefObject } from 'react';

import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { ArrowLink } from '@/components/storefront/arrow-link';
import { BuyBoxActions } from '@/components/storefront/product/buy-box-actions';
import { BuyBoxBrand } from '@/components/storefront/product/buy-box-brand';
import { BuyBoxQuantity } from '@/components/storefront/product/buy-box-quantity';
import { BuyBoxUtilities } from '@/components/storefront/product/buy-box-utilities';
import { resolveGalleryBadge } from '@/components/storefront/product-badge';
import { ProductPrice } from '@/components/storefront/product-price';
import { Spinner } from '@/components/storefront/spinner';
import { useOverlay } from '@/hooks/storefront/use-overlay';
import { useProductPurchase } from '@/hooks/storefront/use-product-purchase';
import { __ } from '@/lib/i18n';
import { galleryMedia } from '@/lib/media';
import { cn, getTranslation } from '@/lib/utils';
import type { ProductBuyBoxData } from '@/types';

import { ProductOptions } from './product-options';
import { ProductRating } from './product-rating';
import { QuickViewGallery } from './quick-view-gallery';

type QuickViewStatus = 'idle' | 'loading' | 'loaded' | 'error';

interface QuickViewModalProps {
    open: boolean;
    status: QuickViewStatus;
    data: ProductBuyBoxData | null;
    onClose: () => void;
}

function QuickViewContent({ data, onClose }: { data: ProductBuyBoxData; onClose: () => void }) {
    const product = useProductPurchase(data, onClose);
    const badge = resolveGalleryBadge(
        product.resolvedVariant ? product.resolvedVariant.in_stock : data.in_stock,
        false,
        product.pricing,
    );

    return (
        <>
            <QuickViewGallery
                media={galleryMedia(data.media, product.resolvedVariant?.media)}
                title={data.title}
                badge={badge}
                activeMediaId={product.resolvedVariant?.media?.id ?? null}
            />

            <div className="p-6 sm:p-8">
                <BuyBoxBrand brand={data.brand} onNavigate={onClose} />

                <h2 id="quick-view-title" className="m-0 pe-8 font-head text-6xl leading-tight font-bold text-ink">
                    {getTranslation(data.title)}
                </h2>

                <ProductRating rating={data.rating} reviewCount={data.review_count} />

                <ProductPrice
                    product={data}
                    variant={product.resolvedVariant}
                    size="lg"
                    as="div"
                    showTaxNote={data.prices_include_tax}
                    className="mt-5"
                />

                <ProductOptions options={data.options} selected={product.selected} onSelect={product.selectOption} />

                <BuyBoxQuantity value={product.quantity} onChange={product.changeQuantity} max={product.maxQuantity} />

                <BuyBoxActions
                    cart={product.cart}
                    label={product.addToCartLabel}
                    canPurchase={product.canPurchase}
                    onAddToCart={product.submitAddToCart}
                    onBuyNow={product.submitBuyNow}
                />

                <BuyBoxUtilities
                    product={data}
                    sku={product.resolvedVariant?.sku ?? data.sku}
                    price={product.pricing.price}
                />

                <ArrowLink
                    href={ProductController.show(
                        data.url_handle,
                        product.resolvedVariant ? { query: { variant: product.resolvedVariant.id } } : undefined,
                    )}
                    onClick={onClose}
                    className="mt-4"
                >
                    {__('View full details')}
                </ArrowLink>
            </div>
        </>
    );
}

export function QuickViewModal({ open, status, data, onClose }: QuickViewModalProps) {
    const panelRef = useOverlay(open, onClose);

    return (
        <div
            className={cn(
                'fixed inset-0 z-110 flex items-center justify-center p-4 sm:p-6',
                open ? 'visible' : 'invisible delay-(--duration-slow)',
            )}
        >
            <div
                onClick={onClose}
                className={cn(
                    'absolute inset-0 bg-black/40 transition-opacity duration-(--duration-slow) ease-out-quart',
                    open ? 'opacity-100' : 'pointer-events-none opacity-0',
                )}
            />
            <div
                ref={panelRef as RefObject<HTMLDivElement>}
                role="dialog"
                aria-modal="true"
                aria-labelledby="quick-view-title"
                className={cn(
                    'relative grid max-h-[85vh] w-[1080px] max-w-full grid-cols-1 overflow-x-hidden overflow-y-auto rounded-md bg-surface shadow-lg transition-[opacity,scale] duration-(--duration-base) ease-out-quart focus:outline-hidden lg:grid-cols-2',
                    open ? 'scale-100 opacity-100' : 'pointer-events-none scale-[0.96] opacity-0',
                )}
            >
                <button
                    type="button"
                    aria-label={__('Close quick view')}
                    onClick={onClose}
                    className="absolute end-4 top-4 z-10 flex size-10 items-center justify-center rounded-full text-muted transition-colors hover:bg-surface-2 focus-visible:outline-offset-2"
                >
                    <XIcon size={18} strokeWidth={2} aria-hidden="true" />
                </button>

                {status === 'loading' && (
                    <div className="col-span-full flex min-h-[420px] items-center justify-center">
                        <Spinner className="size-8 text-muted" aria-label={__('Loading...')} />
                    </div>
                )}
                {status === 'error' && (
                    <div className="col-span-full flex min-h-[420px] items-center justify-center px-6 text-center text-muted">
                        {__('Could not load this product. Please try again.')}
                    </div>
                )}
                {status === 'loaded' && data && <QuickViewContent key={data.id} data={data} onClose={onClose} />}
            </div>
        </div>
    );
}
