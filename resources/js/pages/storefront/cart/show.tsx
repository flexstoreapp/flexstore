import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { CartItems } from '@/components/storefront/cart/cart-items';
import { CartSummary } from '@/components/storefront/cart/cart-summary';
import { CartEmptyState } from '@/components/storefront/cart-empty-state';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { analytics } from '@/lib/analytics';
import { __ } from '@/lib/i18n';
import type { DisplayTaxTotals, StorefrontSharedData } from '@/types';

interface CartShowProps {
    pricesIncludeTax: boolean;
    displayTaxTotals: DisplayTaxTotals;
}

export default function CartShow({ pricesIncludeTax, displayTaxTotals }: CartShowProps) {
    const { cart, activeCurrency } = usePage<StorefrontSharedData>().props;
    const items = cart.items ?? [];
    const hasItems = items.length > 0;
    const itemCount = items.reduce((total, item) => total + item.quantity, 0);

    const viewData = useRef({ cart, activeCurrency });
    useEffect(() => {
        viewData.current = { cart, activeCurrency };
    });
    useEffect(() => {
        if ((viewData.current.cart.items?.length ?? 0) > 0) {
            analytics.viewCart(viewData.current.cart, viewData.current.activeCurrency);
        }
    }, []);

    return (
        <>
            <PageHeader
                crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: __('Cart') }]}
                heading={hasItems ? __('Shopping cart') : undefined}
            />

            {hasItems ? (
                <Section className="mt-6 pb-12">
                    <div className="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_380px] lg:gap-8 xl:grid-cols-[1fr_420px]">
                        <CartItems items={items} />
                        <CartSummary
                            itemCount={itemCount}
                            pricesIncludeTax={pricesIncludeTax}
                            displayTaxTotals={displayTaxTotals}
                        />
                    </div>
                </Section>
            ) : (
                <CartEmptyState />
            )}
        </>
    );
}
