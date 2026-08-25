import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface StatisticProps {
    label: string;
    value: ReactNode;
    description?: ReactNode;
    className?: string;
}

export function Statistic({ label, value, description, className }: StatisticProps) {
    return (
        <div className={cn('px-6', className)}>
            <p className="text-xs font-medium tracking-wide text-muted-foreground">{label}</p>
            <p className="mt-1.5 text-xl font-semibold tracking-tight tabular-nums">{value}</p>
            {description ? <div className="mt-1">{description}</div> : null}
        </div>
    );
}
