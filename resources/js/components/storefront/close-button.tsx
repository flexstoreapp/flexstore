import { XIcon } from 'lucide-react';
import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function CloseButton({ className, ...props }: ComponentProps<'button'>) {
    return (
        <button
            type="button"
            className={cn(
                'flex h-10 w-10 items-center justify-center rounded-full text-muted transition-colors hover:bg-surface-2',
                className,
            )}
            {...props}
        >
            <XIcon size={18} strokeWidth={2} aria-hidden="true" />
        </button>
    );
}
