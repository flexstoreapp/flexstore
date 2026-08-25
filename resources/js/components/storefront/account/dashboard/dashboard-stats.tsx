import { __ } from '@/lib/i18n';
import type { AccountDashboardStats } from '@/types';

export function DashboardStats({ stats }: { stats: AccountDashboardStats }) {
    const tiles = [
        { label: __('Total orders'), value: stats.total },
        { label: __('Fulfilled'), value: stats.fulfilled },
        { label: __('Unfulfilled'), value: stats.unfulfilled },
        { label: __('Downloads'), value: stats.downloads },
    ];

    return (
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
            {tiles.map((tile) => (
                <div key={tile.label} className="rounded-md border border-line bg-surface p-5">
                    <p className="mt-0 mb-0 text-xs font-semibold tracking-label text-muted uppercase">{tile.label}</p>
                    <p className="mt-2 mb-0 text-5xl font-semibold text-ink">{tile.value}</p>
                </div>
            ))}
        </div>
    );
}
