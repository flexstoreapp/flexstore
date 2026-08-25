import * as DashboardController from '@/actions/App/Http/Controllers/Storefront/DashboardController';
import * as HomepageController from '@/actions/App/Http/Controllers/Storefront/HomepageController';
import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import OrderInvoiceController from '@/actions/App/Http/Controllers/Storefront/OrderInvoiceController';
import { buttonVariants } from '@/components/storefront/button';
import { PageHeader } from '@/components/storefront/page-header';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Order } from '@/types';

export function OrderDetailHeader({ order }: { order: Order }) {
    const formatId = useFormatId();
    const formatDate = useFormatDate();
    const orderNumber = formatId(order.id);

    return (
        <>
            <PageHeader
                crumbs={[
                    { label: __('Home'), href: HomepageController.show() },
                    { label: __('Account'), href: DashboardController.index() },
                    { label: __('Orders'), href: OrderController.index() },
                    { label: orderNumber },
                ]}
                heading={__('Order :number', { number: orderNumber })}
                subheading={__('Placed :date', {
                    date: formatDate(order.created_at, { hour: undefined, minute: undefined }),
                })}
                action={
                    <div className="flex flex-wrap items-center gap-3">
                        <a
                            href={OrderInvoiceController(order.id).url}
                            className={cn(buttonVariants({ variant: 'secondary', size: 'md' }))}
                        >
                            {__('Invoice')}
                        </a>
                    </div>
                }
            />
        </>
    );
}
