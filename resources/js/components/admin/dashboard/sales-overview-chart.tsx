import { usePage } from '@inertiajs/react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import { ReportTooltipContent } from '@/components/admin/dashboard/report-tooltip-content';
import { SectionHeading } from '@/components/admin/section-heading';
import { type ChartConfig, ChartContainer, ChartTooltip } from '@/components/ui/chart';
import { useDirection } from '@/components/ui/direction';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { stripTrailingZeros } from '@/lib/utils';
import type { SalesChartData } from '@/types';

export function SalesOverviewChart({ data, from, to }: { data: SalesChartData[]; from: string; to: string }) {
    const { formatMoney } = useFormatMoney();
    const salesChartConfig: ChartConfig = {
        net_sales: {
            label: __('Net sales'),
            color: 'var(--chart-1)',
        },
    };
    const salesTooltipSeries = [{ key: 'net_sales', label: __('Net sales'), format: 'money' }];
    const isRtl = useDirection() === 'rtl';
    const { activeLocale } = usePage().props;

    const chartData =
        data.length > 0
            ? data.map((item) => ({ ...item, net_sales: parseFloat(item.net_sales) }))
            : [from, to].map((date) => ({
                  date: new Date(`${date}T00:00:00`).toLocaleDateString(activeLocale, {
                      month: 'short',
                      day: '2-digit',
                  }),
                  net_sales: 0,
              }));

    return (
        <div className="w-full min-w-0 space-y-4 overflow-hidden">
            <SectionHeading>{__('Sales overview')}</SectionHeading>
            <ScrollArea>
                <ChartContainer config={salesChartConfig} className="h-75 w-full min-w-0 sm:min-w-100 lg:min-w-125">
                    <AreaChart accessibilityLayer data={chartData}>
                        <CartesianGrid strokeDasharray="3 3" vertical={false} />
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            tickFormatter={(value) => value.slice(0, 6)}
                            interval="preserveStartEnd"
                            reversed={isRtl}
                        />
                        <YAxis
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            tickFormatter={(value) => stripTrailingZeros(formatMoney(value))}
                            orientation={isRtl ? 'right' : 'left'}
                        />
                        <ChartTooltip cursor={false} content={<ReportTooltipContent series={salesTooltipSeries} />} />
                        <defs>
                            <linearGradient id="fillSales" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="var(--color-net_sales)" stopOpacity={0.8} />
                                <stop offset="95%" stopColor="var(--color-net_sales)" stopOpacity={0.1} />
                            </linearGradient>
                        </defs>
                        <Area
                            dataKey="net_sales"
                            type="monotone"
                            fill="url(#fillSales)"
                            fillOpacity={0.4}
                            stroke="var(--color-net_sales)"
                            strokeWidth={2}
                        />
                    </AreaChart>
                </ChartContainer>

                <ScrollBar orientation="horizontal" />
            </ScrollArea>
        </div>
    );
}
