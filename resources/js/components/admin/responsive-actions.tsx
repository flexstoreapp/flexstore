import { type UrlMethodPair } from '@inertiajs/core';
import { Link } from '@inertiajs/react';
import { type VariantProps } from 'class-variance-authority';
import { ChevronDownIcon } from 'lucide-react';
import { Fragment, useMemo, useRef, useState } from 'react';

import { Button, type buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useIsomorphicLayoutEffect } from '@/hooks/admin/use-isomorphic-layout-effect';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type ButtonVariant = VariantProps<typeof buttonVariants>['variant'];

export interface ResponsiveAction {
    key: string;
    label: string;
    href?: UrlMethodPair | string;
    external?: boolean;
    prefetch?: boolean;
    onClick?: () => void;
    variant?: ButtonVariant;
    destructive?: boolean;
    pinned?: boolean;
    alwaysOverflow?: boolean;
}

const GAP = 8;

function BarButton({ action }: { action: ResponsiveAction }) {
    const variant = action.variant ?? 'secondary';

    if (action.href !== undefined) {
        return (
            <Button variant={variant} asChild>
                {action.external ? (
                    <a href={action.href as string} target="_blank" rel="noreferrer">
                        {action.label}
                    </a>
                ) : (
                    <Link href={action.href} prefetch={action.prefetch}>
                        {action.label}
                    </Link>
                )}
            </Button>
        );
    }

    return (
        <Button variant={variant} onClick={action.onClick}>
            {action.label}
        </Button>
    );
}

function MenuAction({ action }: { action: ResponsiveAction }) {
    const variant = action.destructive ? 'destructive' : undefined;

    if (action.href !== undefined) {
        return (
            <DropdownMenuItem asChild variant={variant}>
                {action.external ? (
                    <a href={action.href as string} target="_blank" rel="noreferrer">
                        {action.label}
                    </a>
                ) : (
                    <Link href={action.href} prefetch={action.prefetch}>
                        {action.label}
                    </Link>
                )}
            </DropdownMenuItem>
        );
    }

    return (
        <DropdownMenuItem variant={variant} onClick={action.onClick}>
            {action.label}
        </DropdownMenuItem>
    );
}

interface ResponsiveActionsProps {
    actions: ResponsiveAction[];
    maxVisible?: number;
    align?: 'start' | 'center' | 'end';
    className?: string;
}

export function ResponsiveActions({ actions, maxVisible = 5, align = 'end', className }: ResponsiveActionsProps) {
    const containerRef = useRef<HTMLDivElement>(null);
    const measureRef = useRef<HTMLDivElement>(null);
    const [visibleCount, setVisibleCount] = useState(0);

    const { pinned, overflowable, alwaysOverflow } = useMemo(
        () => ({
            pinned: actions.filter((action) => action.pinned),
            overflowable: actions.filter((action) => !action.pinned && !action.alwaysOverflow),
            alwaysOverflow: actions.filter((action) => action.alwaysOverflow),
        }),
        [actions],
    );

    useIsomorphicLayoutEffect(() => {
        const container = containerRef.current;
        const measure = measureRef.current;
        if (!container || !measure) {
            return;
        }

        const widths = Array.from(measure.children, (child) => (child as HTMLElement).offsetWidth);
        const moreWidth = widths[0] ?? 0;
        const pinnedWidths = pinned.map((_, index) => widths[1 + index] ?? 0);
        const overflowWidths = overflowable.map((_, index) => widths[1 + pinned.length + index] ?? 0);

        const pinnedTotal = pinnedWidths.reduce((sum, width) => sum + width + GAP, 0);
        const overflowTotal = overflowWidths.reduce((sum, width) => sum + width + GAP, 0);

        const compute = () => {
            const available = container.clientWidth;

            const fitsWithoutMore =
                alwaysOverflow.length === 0 &&
                pinned.length + overflowable.length <= maxVisible &&
                pinnedTotal + overflowTotal <= available;

            if (fitsWithoutMore) {
                setVisibleCount(overflowable.length);
                return;
            }

            const maxOverflow = Math.max(0, maxVisible - pinned.length - 1);
            let used = pinnedTotal + moreWidth + GAP;
            let count = 0;

            for (let index = 0; index < overflowable.length && count < maxOverflow; index += 1) {
                const next = overflowWidths[index] + GAP;
                if (used + next > available) {
                    break;
                }
                used += next;
                count += 1;
            }

            setVisibleCount(count);
        };

        compute();
        const observer = new ResizeObserver(compute);
        observer.observe(container);

        return () => observer.disconnect();
    }, [pinned, overflowable, alwaysOverflow, maxVisible]);

    const shown = overflowable.slice(0, visibleCount);
    const overflowed = overflowable.slice(visibleCount);
    const menuActions = [...overflowed, ...alwaysOverflow];

    return (
        <div
            ref={containerRef}
            className={cn(
                'relative flex w-full min-w-0 flex-wrap items-center justify-start gap-2 sm:justify-end',
                className,
            )}
        >
            {shown.map((action) => (
                <BarButton key={action.key} action={action} />
            ))}

            {menuActions.length === 1 && <BarButton action={menuActions[0]} />}

            {menuActions.length > 1 && (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="secondary">
                            {__('More actions')}
                            <ChevronDownIcon className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align={align}>
                        {menuActions.map((action, index) => (
                            <Fragment key={action.key}>
                                {action.destructive && index > 0 && <DropdownMenuSeparator />}
                                <MenuAction action={action} />
                            </Fragment>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}

            {pinned.map((action) => (
                <BarButton key={action.key} action={action} />
            ))}

            <div aria-hidden className="pointer-events-none invisible absolute start-0 top-0 h-0 w-0 overflow-hidden">
                <div ref={measureRef} className="flex w-max flex-nowrap items-center gap-2">
                    <Button variant="secondary" className="shrink-0">
                        {__('More actions')}
                        <ChevronDownIcon className="size-4" />
                    </Button>
                    {pinned.map((action) => (
                        <Button key={action.key} variant={action.variant ?? 'secondary'} className="shrink-0">
                            {action.label}
                        </Button>
                    ))}
                    {overflowable.map((action) => (
                        <Button key={action.key} variant={action.variant ?? 'secondary'} className="shrink-0">
                            {action.label}
                        </Button>
                    ))}
                </div>
            </div>
        </div>
    );
}
