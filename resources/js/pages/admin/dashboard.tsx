import { Head } from '@inertiajs/react';

import * as DashboardController from '@/actions/App/Http/Controllers/Admin/DashboardController';
import { ChangeIndicator } from '@/components/admin/change-indicator';
import { RecentOrders } from '@/components/admin/dashboard/recent-orders';
import { SalesOverviewChart } from '@/components/admin/dashboard/sales-overview-chart';
import { TopProducts } from '@/components/admin/dashboard/top-products';
import { Heading } from '@/components/admin/heading';
import { PeriodSelector } from '@/components/admin/period-selector';
import { Statistic } from '@/components/admin/statistic';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __, transChoice } from '@/lib/i18n';
import { STAT_ROW_GRID, statCellClasses, statColsForRow } from '@/lib/statistic-grid';
import { cn } from '@/lib/utils';
import type { Order, DashboardStats, PeriodRanges, SalesChartData, TopProduct } from '@/types';

interface DashboardProps {
    period: string;
    from: string;
    to: string;
    periods: PeriodRanges;
    stats: DashboardStats;
    salesChart: SalesChartData[];
    recentOrders: Order[];
    topProducts: TopProduct[];
}

export default function Dashboard({
    period,
    from,
    to,
    periods,
    stats,
    salesChart,
    recentOrders,
    topProducts,
}: DashboardProps) {
    const { formatMoney } = useFormatMoney();

    const cards = [
        {
            label: __('Total revenue'),
            value: formatMoney(stats.totalRevenue),
            description: <ChangeIndicator change={stats.revenueChange} label={__('vs last period')} />,
        },
        {
            label: __('Orders'),
            value: stats.totalOrders,
            description: <ChangeIndicator change={stats.ordersChange} label={__('vs last period')} />,
        },
        {
            label: __('Customers'),
            value: stats.totalCustomers,
            description: <ChangeIndicator change={stats.customersChange} label={__('vs last period')} />,
        },
        {
            label: __('Avg. order value'),
            value: formatMoney(stats.averageOrderValue),
            description: (
                <p className="text-xs text-muted-foreground">
                    {transChoice(':count total order|:count total orders', stats.totalOrders)}
                </p>
            ),
        },
    ];
    const cols = statColsForRow(cards.length);

    return (
        <>
            <Head title={__('Dashboard')} />

            <div className="@container flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <Heading
                    title={__('Dashboard')}
                    description={__('Welcome back, here’s what’s happening with your business today')}
                />
                <PeriodSelector
                    period={period}
                    from={from}
                    to={to}
                    periods={periods}
                    buildUrl={(query) => DashboardController.index({ query })}
                />
            </div>

            <div className={cn('grid gap-y-6', STAT_ROW_GRID[cards.length])}>
                {cards.map((card, index) => (
                    <Statistic
                        key={card.label}
                        label={card.label}
                        value={card.value}
                        description={card.description}
                        className={statCellClasses(index, cols.mobile, cols.sm, cols.lg)}
                    />
                ))}
            </div>

            <SalesOverviewChart data={salesChart} from={from} to={to} />

            <div className="grid gap-6 lg:grid-cols-7">
                <RecentOrders orders={recentOrders} />
                <TopProducts products={topProducts} />
            </div>
        </>
    );
}

Dashboard.layout = () => ({
    breadcrumbs: [{ title: __('Dashboard'), href: DashboardController.index() }],
});
