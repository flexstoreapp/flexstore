import { TagIcon, XIcon } from 'lucide-react';
import { type ComponentProps, type ReactNode } from 'react';

import { __, __nodes } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export function AppliedCoupon({ code, children }: { code: string; children?: ReactNode }) {
    return (
        <div className="flex h-9 items-center gap-2.5 rounded-md border border-emerald-200 bg-emerald-50 ps-3 pe-1 dark:border-emerald-900 dark:bg-emerald-950">
            <TagIcon className="size-3.5 shrink-0 text-emerald-700 dark:text-emerald-400" aria-hidden="true" />
            <span className="min-w-0 truncate text-sm text-muted-foreground">
                {__nodes(':code applied', {
                    code: (
                        <span dir="ltr" className="font-medium text-foreground">
                            {code}
                        </span>
                    ),
                })}
            </span>
            {children ? <div className="ms-auto">{children}</div> : null}
        </div>
    );
}

export function AppliedCouponRemoveButton({ code, className, ...props }: ComponentProps<'button'> & { code: string }) {
    return (
        <button
            type="button"
            aria-label={__('Remove discount code :code', { code })}
            className={cn(
                'flex size-7 items-center justify-center rounded-md text-muted-foreground transition-colors hover:text-destructive focus-visible:ring-3 focus-visible:ring-ring/50 focus-visible:outline-hidden disabled:opacity-50',
                className,
            )}
            {...props}
        >
            <XIcon className="size-3.5" aria-hidden="true" />
        </button>
    );
}
