import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

function Spinner({ className, ...props }: React.ComponentProps<'span'>) {
    return (
        <span
            role="status"
            aria-label={__('Loading')}
            aria-hidden="true"
            className={cn(
                'size-3.5 shrink-0 animate-spin rounded-full border border-current border-t-transparent',
                className,
            )}
            {...props}
        />
    );
}

export { Spinner };
