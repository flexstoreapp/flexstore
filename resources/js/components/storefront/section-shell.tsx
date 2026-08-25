import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export function Section({ className, children }: { className?: string; children: ReactNode }) {
    return <section className={cn('mx-auto mt-8 max-w-page px-6 sm:mt-12', className)}>{children}</section>;
}

export function SectionTitle({ className, children }: { className?: string; children: ReactNode }) {
    return <h2 className={cn('m-0 text-5xl font-semibold text-ink', className)}>{children}</h2>;
}
