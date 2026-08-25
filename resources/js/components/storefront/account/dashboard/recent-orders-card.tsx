import { Link } from '@inertiajs/react';
import { PackageIcon } from 'lucide-react';

import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import { AccountEmptyState } from '@/components/storefront/account/account-empty-state';
import { buttonVariants } from '@/components/storefront/button';
import { StatusBadge } from '@/components/storefront/status-badge';
import { useFormatDate } from '@/hooks/use-format-date';
import { useFormatId } from '@/hooks/use-format-id';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __, transChoice } from '@/lib/i18n';
import { orderStatus } from '@/lib/order-status';
import type { AccountOrderSummary } from '@/types';

export function RecentOrdersCard({ orders }: { orders: AccountOrderSummary[] }) {
    const { formatMoney } = useFormatMoney();
    const formatDate = useFormatDate();
    const formatId = useFormatId();

    return (
        <section className="overflow-hidden rounded-md border border-line bg-surface">
            <div className="flex items-center justify-between border-b border-line px-6 py-4">
                <h2 className="m-0 text-xl font-semibold text-ink">{__('Recent orders')}</h2>
                <Link
                    href={OrderController.index()}
                    className="text-sm font-semibold text-primary underline-offset-2 hover:underline"
                >
                    {__('View all')}
                </Link>
            </div>

            {orders.length === 0 ? (
                <AccountEmptyState
                    icon={<PackageIcon size={28} strokeWidth={1.6} aria-hidden="true" />}
                    title={__('No orders yet')}
                    description={__('When you place an order, it will show up here.')}
                    action={
                        <Link
                            href={ShopController.index()}
                            className={buttonVariants({ variant: 'primary', size: 'md' })}
                        >
                            {__('Browse the shop')}
                        </Link>
                    }
                />
            ) : (
                <ul className="m-0 list-none p-0">
                    {orders.map((order) => {
                        const status = orderStatus(order);
                        const badge = <StatusBadge tone={status.tone}>{status.label}</StatusBadge>;

                        return (
                            <li key={order.id} className="border-b border-line last:border-b-0">
                                <Link
                                    href={OrderController.show(order.id)}
                                    className="flex items-center gap-4 px-6 py-4 transition-colors can-hover:hover:bg-surface-2"
                                >
                                    <div className="min-w-0 flex-1">
                                        <p className="mt-0 mb-0 w-fit font-semibold text-ink underline-offset-2 transition-colors hover:text-primary hover:underline">
                                            {formatId(order.id)}
                                        </p>
                                        <p className="mt-0.5 mb-0 text-sm text-muted">
                                            {formatDate(order.created_at, { hour: undefined, minute: undefined })}
                                            {' · '}
                                            {transChoice(':count item|:count items', order.items_sum_quantity ?? 0)}
                                        </p>
                                        <div className="mt-2 sm:hidden">{badge}</div>
                                    </div>
                                    <span className="hidden sm:block">{badge}</span>
                                    <span className="w-24 text-end font-semibold text-ink">
                                        {formatMoney(order.total, order.currency_code)}
                                    </span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}
