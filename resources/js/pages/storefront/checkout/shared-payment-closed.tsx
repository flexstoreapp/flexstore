import { CheckIcon, ClockIcon, TagIcon, XIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { StatusPanel, StatusPrimaryLink } from '@/components/storefront/status-panel';
import { __ } from '@/lib/i18n';

type ClosedStatus = 'paid' | 'expired' | 'discount' | 'inactive';

const iconProps = { size: 34, strokeWidth: 1.6 };

function panelFor(status: ClosedStatus): {
    variant: 'success' | 'neutral' | 'error';
    icon: ReactNode;
    title: string;
    description: string;
} {
    switch (status) {
        case 'paid':
            return {
                variant: 'success',
                icon: <CheckIcon {...iconProps} />,
                title: __('Already paid'),
                description: __('This order has already been paid, so there is nothing left to do here.'),
            };
        case 'expired':
            return {
                variant: 'neutral',
                icon: <ClockIcon {...iconProps} />,
                title: __('Link expired'),
                description: __('This payment link has expired. Ask for a new one to complete the payment.'),
            };
        case 'discount':
            return {
                variant: 'neutral',
                icon: <TagIcon {...iconProps} />,
                title: __('Discount expired'),
                description: __(
                    'The discount on this checkout is no longer valid, so this link can no longer be paid. Ask for a new one.',
                ),
            };
        default:
            return {
                variant: 'error',
                icon: <XIcon {...iconProps} />,
                title: __('Link no longer active'),
                description: __('This payment link is no longer active. It may have been replaced by a newer one.'),
            };
    }
}

export default function SharedPaymentClosed({ status }: { status: ClosedStatus }) {
    const panel = panelFor(status);

    return (
        <>
            <PageHeader
                crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: __('Complete payment') }]}
            />

            <Section className="mt-6 pb-12">
                <StatusPanel
                    variant={panel.variant}
                    icon={panel.icon}
                    title={panel.title}
                    description={panel.description}
                    actions={
                        <StatusPrimaryLink href={ShopController.index()}>{__('Continue shopping')}</StatusPrimaryLink>
                    }
                />
            </Section>
        </>
    );
}
