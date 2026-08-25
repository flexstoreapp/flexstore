import { Link, router } from '@inertiajs/react';
import { PackageIcon } from 'lucide-react';

import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import * as ShopController from '@/actions/App/Http/Controllers/Storefront/ShopController';
import { AccountEmptyState } from '@/components/storefront/account/account-empty-state';
import { OrderCard } from '@/components/storefront/account/order-card';
import { OrderStatusFilter } from '@/components/storefront/account/order-status-filter';
import { Button, buttonVariants } from '@/components/storefront/button';
import { Pagination } from '@/components/storefront/shop/pagination';
import { __ } from '@/lib/i18n';
import type { AccountOrder, Paginated } from '@/types';

interface OrdersProps {
    orders: Paginated<AccountOrder>;
    status: string | null;
}

export default function Orders({ orders, status }: OrdersProps) {
    const navigate = (params: Record<string, string | number>) => {
        router.get(OrderController.index().url, params, {
            preserveScroll: true,
            preserveState: true,
            only: ['orders', 'status'],
        });
    };

    return (
        <>
            <OrderStatusFilter active={status} onChange={(value) => navigate(value ? { status: value } : {})} />

            {orders.data.length === 0 ? (
                <div className="rounded-md border border-line bg-surface">
                    {status ? (
                        <AccountEmptyState
                            icon={<PackageIcon size={28} strokeWidth={1.6} aria-hidden="true" />}
                            title={__('No matching orders')}
                            description={__('No orders match this filter. Try a different status.')}
                            action={
                                <Button variant="outline" size="md" onClick={() => navigate({})}>
                                    {__('Show all orders')}
                                </Button>
                            }
                        />
                    ) : (
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
                    )}
                </div>
            ) : (
                <>
                    {orders.data.map((order) => (
                        <OrderCard key={order.id} order={order} />
                    ))}

                    <Pagination
                        currentPage={orders.current_page}
                        lastPage={orders.last_page}
                        onPage={(page) => navigate(status ? { status, page } : { page })}
                    />
                </>
            )}
        </>
    );
}

Orders.title = __('Orders');
