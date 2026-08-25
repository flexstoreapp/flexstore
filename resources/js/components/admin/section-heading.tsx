import { cn } from '@/lib/utils';

export function SectionHeading({ className, ...props }: React.ComponentPropsWithoutRef<'h2'>) {
    return <h2 className={cn('text-sm font-semibold', className)} {...props} />;
}
