import { Badge } from '@/components/ui/badge';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function ProBadge({ className }: { className?: string }) {
    return (
        <Badge
            variant="outline"
            className={cn(
                'border-amber-400/40 bg-amber-400/10 px-1.5 py-0 text-[10px] font-semibold tracking-wide text-amber-600 dark:text-amber-400',
                className,
            )}
        >
            {__('Pro')}
        </Badge>
    );
}
