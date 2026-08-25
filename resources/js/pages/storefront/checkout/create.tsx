import { usePage } from '@inertiajs/react';

import * as CartController from '@/actions/App/Http/Controllers/Storefront/CartController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { CartEmptyState } from '@/components/storefront/cart-empty-state';
import { CheckoutProvider, type CheckoutPageProps } from '@/components/storefront/checkout/checkout-context';
import { CheckoutForm } from '@/components/storefront/checkout/checkout-form';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { __ } from '@/lib/i18n';
import type { StorefrontSharedData } from '@/types';

export default function CheckoutCreate(props: CheckoutPageProps) {
    const { cart } = usePage<StorefrontSharedData>().props;
    const hasItems = (cart.items ?? []).length > 0;

    return (
        <>
            <PageHeader
                crumbs={[
                    { label: __('Home'), href: HomepageController.show() },
                    { label: __('Cart'), href: CartController.show() },
                    { label: __('Checkout') },
                ]}
                heading={hasItems ? __('Checkout') : undefined}
            />

            {hasItems ? (
                <CheckoutProvider {...props}>
                    <Section className="mt-6 pb-12">
                        <CheckoutForm />
                    </Section>
                </CheckoutProvider>
            ) : (
                <CartEmptyState />
            )}
        </>
    );
}
