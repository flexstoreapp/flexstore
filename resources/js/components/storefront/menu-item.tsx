import { ChevronRightIcon } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

import { DropdownItem } from './dropdown-item';
import { FLYOUT_PANEL_EDGES_ALL } from './flyout';

export interface MenuItemData {
    label: string;
    href: string;
    children?: MenuItemData[];
    submenu?: ReactNode;
}

// Per-level classes are hardcoded 1-3 because Tailwind only generates the
// group-hover/lN utilities for literal group names; nesting is capped at depth 3.
const LEVELS: Record<number, { group: string; row: string; reveal: string }> = {
    1: {
        group: 'group/l1',
        row: 'group-hover/l1:bg-surface-2 group-hover/l1:text-primary group-focus-within/l1:bg-surface-2 group-focus-within/l1:text-primary',
        reveal: 'group-hover/l1:visible group-hover/l1:opacity-100 group-hover/l1:scale-x-100 group-hover/l1:delay-60 group-hover/l1:duration-(--duration-fast) group-focus-within/l1:visible group-focus-within/l1:opacity-100 group-focus-within/l1:scale-x-100',
    },
    2: {
        group: 'group/l2',
        row: 'group-hover/l2:bg-surface-2 group-hover/l2:text-primary group-focus-within/l2:bg-surface-2 group-focus-within/l2:text-primary',
        reveal: 'group-hover/l2:visible group-hover/l2:opacity-100 group-hover/l2:scale-x-100 group-hover/l2:delay-60 group-hover/l2:duration-(--duration-fast) group-focus-within/l2:visible group-focus-within/l2:opacity-100 group-focus-within/l2:scale-x-100',
    },
    3: {
        group: 'group/l3',
        row: 'group-hover/l3:bg-surface-2 group-hover/l3:text-primary group-focus-within/l3:bg-surface-2 group-focus-within/l3:text-primary',
        reveal: 'group-hover/l3:visible group-hover/l3:opacity-100 group-hover/l3:scale-x-100 group-hover/l3:delay-60 group-hover/l3:duration-(--duration-fast) group-focus-within/l3:visible group-focus-within/l3:opacity-100 group-focus-within/l3:scale-x-100',
    },
};

interface MenuItemProps extends MenuItemData {
    depth?: number;
}

export function MenuItem({ label, href, children, submenu, depth = 1 }: MenuItemProps) {
    const level = Math.min(depth, 3);
    const { group, row, reveal } = LEVELS[level];
    const hasSubmenu = Boolean(submenu) || Boolean(children?.length);

    return (
        <div className={cn('relative', group)}>
            <DropdownItem
                href={href}
                label={label}
                hoverClass={row}
                trailing={
                    hasSubmenu && (
                        <ChevronRightIcon
                            size={12}
                            strokeWidth={2}
                            aria-hidden="true"
                            className="ms-auto text-muted rtl:-scale-x-100"
                        />
                    )
                }
            />

            {hasSubmenu && (
                <div
                    className={cn(
                        'invisible absolute start-full top-0 origin-left scale-x-95 opacity-0 transition-[opacity,scale,visibility] delay-0 duration-0 ease-out-quart rtl:origin-right',
                        reveal,
                    )}
                >
                    {submenu ?? (
                        <div
                            className={cn(
                                'w-[240px] rounded-md border border-line bg-surface shadow-md',
                                FLYOUT_PANEL_EDGES_ALL,
                            )}
                        >
                            {children?.map((child) => (
                                <MenuItem key={child.label} {...child} depth={depth + 1} />
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
