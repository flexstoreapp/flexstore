import { usePage } from '@inertiajs/react';
import { HeartIcon, Share2Icon } from 'lucide-react';

import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { useWishlist } from '@/hooks/storefront/use-product-lists';
import { analytics } from '@/lib/analytics';
import { __, __nodes } from '@/lib/i18n';
import { cn, getTranslation } from '@/lib/utils';
import type { StorefrontSharedData, TranslatableField } from '@/types';

interface BuyBoxUtilitiesProduct {
    id: number;
    url_handle: string;
    title: TranslatableField;
}

interface BuyBoxUtilitiesProps {
    product: BuyBoxUtilitiesProduct;
    sku: string | null;
    price: string | null;
}

const actionClass =
    'flex items-center gap-2 rounded-sm p-1 -m-1 transition-colors can-hover:hover:text-primary focus-visible:outline-offset-2';

export function BuyBoxUtilities({ product, sku, price }: BuyBoxUtilitiesProps) {
    const { activeCurrency } = usePage<StorefrontSharedData>().props;
    const wishlist = useWishlist(product.id, () => analytics.addToWishlist(product, price, activeCurrency));

    const share = () => {
        const url = new URL(ProductController.show(product.url_handle).url, window.location.origin).toString();
        const canShare = typeof navigator.share === 'function';
        analytics.share(product.id, canShare ? 'web_share' : 'copy');

        if (canShare) {
            void navigator.share({ title: getTranslation(product.title), url });
        } else {
            void navigator.clipboard?.writeText(url);
        }
    };

    return (
        <>
            <div className="mt-5 flex flex-wrap items-center gap-x-6 gap-y-2 text-muted">
                <button
                    type="button"
                    aria-pressed={wishlist.active}
                    onClick={wishlist.toggle}
                    className={cn(actionClass, wishlist.active && 'text-primary')}
                >
                    <HeartIcon
                        size={15}
                        strokeWidth={1.7}
                        fill={wishlist.active ? 'currentColor' : 'none'}
                        aria-hidden="true"
                    />
                    {__('Add to wishlist')}
                </button>
                <button type="button" onClick={share} className={actionClass}>
                    <Share2Icon size={15} strokeWidth={1.7} aria-hidden="true" />
                    {__('Share')}
                </button>
            </div>

            {sku && (
                <div className="mt-3 border-t border-line pt-3 text-sm font-semibold text-ink">
                    <span dir="ltr">
                        {__nodes('SKU: :sku', {
                            sku: <bdi className="font-normal text-muted">{sku}</bdi>,
                        })}
                    </span>
                </div>
            )}
        </>
    );
}
