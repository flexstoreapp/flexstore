import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

interface SubmitButtonProps extends React.ComponentProps<typeof Button> {
    processing: boolean;
}

export function SubmitButton({ processing, children, className, type = 'submit', ...props }: SubmitButtonProps) {
    return (
        <Button type={type} className={cn('relative', className)} disabled={processing} {...props}>
            <span
                className={cn('flex items-center gap-2 transition-opacity', {
                    'opacity-0': processing,
                    'opacity-100': !processing,
                })}
            >
                {children}
            </span>
            <span
                className={cn('absolute inset-0 flex items-center justify-center transition-opacity', {
                    'opacity-100': processing,
                    'opacity-0': !processing,
                })}
            >
                {processing && <Spinner />}
            </span>
        </Button>
    );
}
