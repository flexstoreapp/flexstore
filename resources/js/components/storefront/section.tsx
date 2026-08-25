import type { ComponentProps } from 'react';

import { cn } from '@/lib/utils';

export function Section({ className, children, ...props }: ComponentProps<'section'>) {
    return (
        <section className={cn('mx-auto w-full max-w-page px-6', className)} {...props}>
            {children}
        </section>
    );
}
