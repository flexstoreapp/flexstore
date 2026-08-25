import { XIcon } from 'lucide-react';

import * as CartController from '@/actions/App/Http/Controllers/Storefront/CartController';
import * as CheckoutController from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { StatusPanel, StatusPrimaryLink, StatusSecondaryLink } from '@/components/storefront/status-panel';
import { __ } from '@/lib/i18n';

export default function CheckoutCancel() {
    return (
        <>
            <PageHeader crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: __('Checkout') }]} />

            <Section className="mt-6 pb-12">
                <StatusPanel
                    variant="error"
                    icon={<XIcon size={34} strokeWidth={1.6} />}
                    title={__('Checkout canceled')}
                    description={__(
                        'You left before completing payment, so nothing was charged. Your cart is saved whenever you’re ready to try again.',
                    )}
                    actions={
                        <>
                            <StatusPrimaryLink href={CheckoutController.create()}>
                                {__('Return to checkout')}
                            </StatusPrimaryLink>
                            <StatusSecondaryLink href={CartController.show()}>{__('Review cart')}</StatusSecondaryLink>
                        </>
                    }
                />
            </Section>
        </>
    );
}
