import { cn } from '@/lib/utils';

export function HelpBlock({ children, className, ...props }: React.HTMLAttributes<HTMLParagraphElement>) {
    return (
        children && (
            <p {...props} className={cn('text-sm text-muted-foreground', className)}>
                {children}
            </p>
        )
    );
}
