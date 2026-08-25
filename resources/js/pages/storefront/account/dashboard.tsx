import { AccountDetailsCard } from '@/components/storefront/account/dashboard/account-details-card';
import { DashboardStats } from '@/components/storefront/account/dashboard/dashboard-stats';
import { DefaultAddressCard } from '@/components/storefront/account/dashboard/default-address-card';
import { RecentOrdersCard } from '@/components/storefront/account/dashboard/recent-orders-card';
import { __ } from '@/lib/i18n';
import type { AccountDashboardStats, AccountOrderSummary, CustomerAddress } from '@/types';

interface DashboardProps {
    stats: AccountDashboardStats;
    recentOrders: AccountOrderSummary[];
    defaultAddress: CustomerAddress | null;
}

export default function Dashboard({ stats, recentOrders, defaultAddress }: DashboardProps) {
    return (
        <>
            <DashboardStats stats={stats} />
            <RecentOrdersCard orders={recentOrders} />
            <div className="grid gap-6 md:grid-cols-2">
                <DefaultAddressCard address={defaultAddress} />
                <AccountDetailsCard />
            </div>
        </>
    );
}

Dashboard.title = __('Dashboard');
