import { CheckIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';

import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { StatusPanel, StatusPrimaryLink, StatusSecondaryLink } from '@/components/storefront/status-panel';
import { useFormatId } from '@/hooks/use-format-id';
import { analytics, type PurchaseOrder } from '@/lib/analytics';
import { __ } from '@/lib/i18n';

interface CheckoutSuccessProps {
    order: PurchaseOrder & { trackUrl: string | null };
    emailConfirmation: boolean;
    isBuyer: boolean;
}

export default function CheckoutSuccess({ order, emailConfirmation, isBuyer }: CheckoutSuccessProps) {
    const formatId = useFormatId();

    const purchaseTracked = useRef(false);
    useEffect(() => {
        if (purchaseTracked.current || !isBuyer) {
            return;
        }
        purchaseTracked.current = true;
        analytics.purchase(order);
    }, [order, isBuyer]);

    const orderNumber = formatId(order.id);
    const confirmedWithEmail = __(
        'Your order :number is confirmed. A confirmation email with your receipt is on its way.',
        { number: orderNumber },
    );
    const confirmedOnly = __('Your order :number is confirmed.', { number: orderNumber });
    const description = isBuyer
        ? emailConfirmation
            ? confirmedWithEmail
            : confirmedOnly
        : __('Your payment went through and order :number is confirmed. We have let the buyer know.', {
              number: orderNumber,
          });

    return (
        <>
            <PageHeader crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: __('Checkout') }]} />

            <Section className="mt-6 pb-12">
                <StatusPanel
                    variant="success"
                    icon={<CheckIcon size={34} strokeWidth={1.6} />}
                    title={isBuyer ? __('Thank you for your order') : __('Payment complete')}
                    description={description}
                    actions={
                        <>
                            {order.trackUrl !== null && (
                                <>
                                    <StatusPrimaryLink href={order.trackUrl}>
                                        {__('Track your order')}
                                    </StatusPrimaryLink>
                                    <StatusSecondaryLink href={HomepageController.show()}>
                                        {__('Continue shopping')}
                                    </StatusSecondaryLink>
                                </>
                            )}
                        </>
                    }
                />
            </Section>
        </>
    );
}
