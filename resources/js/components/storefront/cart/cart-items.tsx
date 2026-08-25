import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import * as CartController from '@/actions/App/Http/Controllers/Storefront/CartController';
import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import { ArrowLink } from '@/components/storefront/arrow-link';
import { CartLineItem } from '@/components/storefront/cart/cart-line-item';
import { analytics } from '@/lib/analytics';
import { __ } from '@/lib/i18n';
import type { CartItem, StorefrontSharedData } from '@/types';

export function CartItems({ items }: { items: CartItem[] }) {
    const { activeCurrency } = usePage<StorefrontSharedData>().props;
    const [clearing, setClearing] = useState(false);

    const clear = () => {
        setClearing(true);
        router.delete(CartController.destroy().url, {
            preserveScroll: true,
            onSuccess: () => items.forEach((item) => analytics.removeFromCart(item, activeCurrency)),
            onFinish: () => setClearing(false),
        });
    };

    return (
        <div className="overflow-hidden rounded-md border border-line bg-surface">
            <div className="lg:grid lg:grid-cols-[3fr_1fr_1.5fr_0.9fr]">
                <div className="hidden gap-6 border-b border-line bg-surface-2 px-6 py-4 text-2xs font-bold tracking-label whitespace-nowrap text-muted uppercase lg:col-span-4 lg:grid lg:grid-cols-subgrid xl:gap-8">
                    <span>{__('Product')}</span>
                    <span className="text-center">{__('Unit price')}</span>
                    <span className="text-center">{__('Quantity')}</span>
                    <span className="text-end">{__('Line total')}</span>
                </div>

                <ul className="m-0 list-none p-0 lg:col-span-4 lg:grid lg:grid-cols-subgrid">
                    {items.map((item) => (
                        <CartLineItem key={item.id} item={item} />
                    ))}
                </ul>
            </div>

            <div className="flex flex-wrap items-center gap-4 border-t border-line px-5 py-4 lg:px-6">
                <ArrowLink href={ShopController.index()} direction="back" size="sm">
                    {__('Continue shopping')}
                </ArrowLink>
                <button
                    type="button"
                    onClick={clear}
                    disabled={clearing}
                    className="ms-auto rounded-xs text-sm font-semibold text-muted transition focus-visible:outline-offset-2 disabled:opacity-50 can-hover:hover:text-error"
                >
                    {__('Clear cart')}
                </button>
            </div>
        </div>
    );
}
