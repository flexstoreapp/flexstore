import { cn } from '@/lib/utils';

export function ActionBar({ className, children }: React.PropsWithChildren<{ className?: string }>) {
    return <div className={cn('flex items-center justify-between gap-4', className)}>{children}</div>;
}
