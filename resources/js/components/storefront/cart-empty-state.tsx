import { Link } from '@inertiajs/react';
import { ShoppingCartIcon } from 'lucide-react';

import * as CategoryController from '@/actions/App/Http/Controllers/Storefront/CategoryController';
import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import { buttonVariants } from '@/components/storefront/button';
import { Section } from '@/components/storefront/section';
import { StatusPanel } from '@/components/storefront/status-panel';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function CartEmptyState() {
    return (
        <Section className="mt-6 pb-12">
            <StatusPanel
                icon={<ShoppingCartIcon size={34} strokeWidth={1.6} aria-hidden="true" />}
                title={__('Your cart is empty')}
                description={__('You haven’t added any items to your cart yet.')}
                actions={
                    <>
                        <Link href={ShopController.index()} className={cn(buttonVariants({ size: 'md' }))}>
                            {__('Browse the shop')}
                        </Link>
                        <Link
                            href={CategoryController.index()}
                            className={cn(buttonVariants({ variant: 'outline', size: 'md' }))}
                        >
                            {__('Browse categories')}
                        </Link>
                    </>
                }
            />
        </Section>
    );
}
