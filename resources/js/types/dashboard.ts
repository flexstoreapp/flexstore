import type { TranslatableField } from '.';
import type { MediaItem } from './media';

export interface DashboardStats {
    totalRevenue: string;
    totalOrders: number;
    totalCustomers: number;
    revenueChange: number;
    ordersChange: number;
    customersChange: number;
    averageOrderValue: string;
}

export interface SalesChartData {
    date: string;
    net_sales: string;
}

export interface TopProduct {
    id: number;
    title: TranslatableField;
    featured_media: MediaItem | null;
    total_sold: number;
    revenue: string;
}

export interface PeriodRanges {
    [preset: string]: { from: string; to: string };
}
