import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import { PageHeader } from '@/components/storefront/page-header';
import { Section } from '@/components/storefront/section';
import { TrackOrderForm } from '@/components/storefront/track-order/track-order-form';
import { TrackOrderResult } from '@/components/storefront/track-order/track-order-result';
import { __ } from '@/lib/i18n';
import type { TrackedOrderData } from '@/types';

interface TrackOrderProps {
    order: TrackedOrderData | null;
}

export default function TrackOrder({ order }: TrackOrderProps) {
    return (
        <>
            <PageHeader
                crumbs={[{ label: __('Home'), href: HomepageController.show() }, { label: __('Track order') }]}
                heading={__('Track order')}
            />

            <Section className="mt-6 flex flex-col gap-6 pb-12">
                <TrackOrderForm
                    key={order?.id ?? 'empty'}
                    defaults={order ? { orderNumber: String(order.id), email: order.email } : undefined}
                />
                {order && <TrackOrderResult order={order} />}
            </Section>
        </>
    );
}
