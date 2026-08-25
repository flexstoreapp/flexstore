import { ArrowDownRightIcon, ArrowUpRightIcon } from 'lucide-react';

import { cn } from '@/lib/utils';

export function ChangeIndicator({ change, label }: { change: number; label: string }) {
    const isPositive = change >= 0;
    const Icon = isPositive ? ArrowUpRightIcon : ArrowDownRightIcon;

    return (
        <p className="flex flex-wrap items-center gap-x-1 gap-y-0.5 text-xs text-muted-foreground">
            <span
                dir="ltr"
                className={cn(
                    'inline-flex items-center gap-1',
                    isPositive ? 'text-emerald-700 dark:text-emerald-400' : 'text-destructive',
                )}
            >
                <Icon className="size-3 rtl:-scale-x-100" />
                <span>
                    {change > 0 ? '+' : ''}
                    {change}%
                </span>
            </span>
            <span className="whitespace-nowrap">{label}</span>
        </p>
    );
}
