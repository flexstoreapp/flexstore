import { Link } from '@inertiajs/react';
import type { ComponentProps, ReactNode } from 'react';

import { buttonVariants } from '@/components/storefront/button';
import { cn } from '@/lib/utils';

type LinkHref = ComponentProps<typeof Link>['href'];

type StatusVariant = 'success' | 'neutral' | 'error';

const VARIANT_STYLES: Record<StatusVariant, string> = {
    success: 'bg-success/10 text-success',
    neutral: 'bg-surface-2 text-muted',
    error: 'bg-error/10 text-error',
};

interface StatusPanelProps {
    variant?: StatusVariant;
    icon: ReactNode;
    title: string;
    description: ReactNode;
    actions?: ReactNode;
}

export function StatusPanel({ variant = 'neutral', icon, title, description, actions }: StatusPanelProps) {
    return (
        <div className="mx-auto max-w-[640px] rounded-md border border-line bg-surface px-6 py-16 text-center">
            <span
                aria-hidden="true"
                className={cn(
                    'mx-auto flex h-20 w-20 items-center justify-center rounded-full',
                    VARIANT_STYLES[variant],
                )}
            >
                {icon}
            </span>
            <h1 className="m-0 mt-6 font-head text-7xl font-bold text-ink">{title}</h1>
            <p className="mx-auto mt-2.5 mb-7 max-w-[52ch] text-muted">{description}</p>
            {actions && <div className="flex flex-wrap justify-center gap-3">{actions}</div>}
        </div>
    );
}

export function StatusPrimaryLink({ href, children }: { href: LinkHref; children: ReactNode }) {
    return (
        <Link href={href} className={cn(buttonVariants({ variant: 'primary', size: 'md' }))}>
            {children}
        </Link>
    );
}

export function StatusSecondaryLink({ href, children }: { href: LinkHref; children: ReactNode }) {
    return (
        <Link href={href} className={cn(buttonVariants({ variant: 'outline', size: 'md' }))}>
            {children}
        </Link>
    );
}
