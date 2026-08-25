import { Link } from '@inertiajs/react';

import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import OrderInvoiceController from '@/actions/App/Http/Controllers/Storefront/OrderInvoiceController';
import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { buttonVariants } from '@/components/storefront/button';
import { LineItem } from '@/components/storefront/line-item';
import { StatusBadge } from '@/components/storefront/status-badge';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __, transChoice } from '@/lib/i18n';
import { orderStatus } from '@/lib/order-status';
import { cn, getTranslation } from '@/lib/utils';
import type { AccountOrder } from '@/types';

export function OrderCard({ order }: { order: AccountOrder }) {
    const { formatMoney } = useFormatMoney();
    const formatDate = useFormatDate();
    const formatId = useFormatId();
    const status = orderStatus(order);

    return (
        <section className="overflow-hidden rounded-md border border-line bg-surface">
            <div className="flex flex-col gap-3 border-b border-line bg-surface-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div className="flex justify-between gap-x-6 sm:justify-start sm:gap-x-10">
                    <Meta label={__('Order')} value={formatId(order.id)} href={OrderController.show(order.id).url} />
                    <Meta
                        label={__('Placed')}
                        value={formatDate(order.created_at, { hour: undefined, minute: undefined })}
                    />
                    <Meta label={__('Total')} value={formatMoney(order.total, order.currency_code)} />
                </div>
                <StatusBadge tone={status.tone} className="self-start sm:self-auto">
                    {status.label}
                </StatusBadge>
            </div>

            <ul className="m-0 list-none divide-y divide-line px-5">
                {order.items.map((item) => (
                    <LineItem
                        key={item.id}
                        title={getTranslation(item.product_title)}
                        variantTitle={item.variant_title}
                        url={item.product?.is_active ? ProductController.show(item.product.url_handle) : null}
                        media={item.media}
                        quantity={item.quantity}
                        price={item.total_price}
                        currencyCode={order.currency_code}
                        className="py-4"
                    />
                ))}
                {order.items_count > order.items.length && (
                    <li className="py-4 text-sm text-muted">
                        {transChoice('+ :count more item|+ :count more items', order.items_count - order.items.length)}
                    </li>
                )}
            </ul>

            <div className="flex items-center justify-end gap-3 border-t border-line px-5 py-4">
                <div className="hidden items-center gap-3 sm:flex">
                    <a
                        href={OrderInvoiceController(order.id).url}
                        className={cn(buttonVariants({ variant: 'secondary', size: 'sm' }))}
                    >
                        {__('Invoice')}
                    </a>
                </div>

                <Link
                    href={OrderController.show(order.id)}
                    className={cn(buttonVariants({ variant: 'primary', size: 'sm' }))}
                >
                    {__('View details')}
                </Link>
            </div>
        </section>
    );
}

function Meta({ label, value, href }: { label: string; value: string; href?: string }) {
    return (
        <div>
            <p className="mt-0 mb-0 text-2xs font-semibold tracking-label text-muted uppercase">{label}</p>
            {href ? (
                <Link
                    href={href}
                    className="mt-0.5 mb-0 block w-fit text-sm font-semibold whitespace-nowrap text-ink underline-offset-2 transition-colors hover:text-primary hover:underline"
                >
                    {value}
                </Link>
            ) : (
                <p className="mt-0.5 mb-0 text-sm font-semibold whitespace-nowrap text-ink">{value}</p>
            )}
        </div>
    );
}
