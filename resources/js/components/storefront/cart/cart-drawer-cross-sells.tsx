import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import CartCrossSellController from '@/actions/App/Http/Controllers/Storefront/CartCrossSellController';
import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { ContainedMedia } from '@/components/storefront/contained-media';
import { useFormatMoney } from '@/hooks/use-format-money';
import { httpGet, isAbortError } from '@/lib/http';
import { __ } from '@/lib/i18n';
import { getTranslation } from '@/lib/utils';
import type { ProductData, StorefrontSharedData } from '@/types';

export function CartDrawerCrossSells({ open, onNavigate }: { open: boolean; onNavigate: () => void }) {
    const { formatMoney } = useFormatMoney();
    const { cart } = usePage<StorefrontSharedData>().props;
    const [products, setProducts] = useState<ProductData[]>([]);
    const cartSignature = (cart.items ?? []).map((item) => item.product_id).join(',');

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();

        httpGet<ProductData[]>(CartCrossSellController(), { signal: controller.signal })
            .then(setProducts)
            .catch((error: unknown) => {
                if (!isAbortError(error)) {
                    setProducts([]);
                }
            });

        return () => controller.abort();
    }, [open, cartSignature]);

    if (products.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="cart-drawer-cross-sell-heading"
            className="shrink-0 border-t border-line px-5 pt-4 sm:px-6"
        >
            <h3 id="cart-drawer-cross-sell-heading" className="font-head font-semibold text-ink">
                {__('Pairs well with')}
            </h3>
            <ul className="mt-1 divide-y divide-line">
                {products.map((product) => {
                    const title = getTranslation(product.title);

                    return (
                        <li key={product.id}>
                            <Link
                                href={ProductController.show(product.url_handle)}
                                onClick={onNavigate}
                                className="flex items-center gap-3 py-3"
                            >
                                <span className="relative block size-12 shrink-0 overflow-hidden rounded-md bg-surface-2">
                                    <ContainedMedia media={product.featured_media} alt={title} source="small" />
                                </span>
                                <span className="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{title}</span>
                                <span className="shrink-0 text-sm font-bold text-ink">
                                    {product.price !== null
                                        ? formatMoney(product.price)
                                        : product.price_range && formatMoney(product.price_range[0])}
                                </span>
                            </Link>
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
