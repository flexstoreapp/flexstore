import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { ComponentProps, ReactNode } from 'react';

import { cn } from '@/lib/utils';

const baseClass = 'flex items-center gap-3 px-5 h-[46px] border-b border-line font-normal transition-colors';

export const dropdownItemHover =
    'group-hover/l1:bg-surface-2 group-hover/l1:text-primary group-focus-within/l1:bg-surface-2 group-focus-within/l1:text-primary';

export const dropdownItemHoverDestructive =
    'group-hover/l1:bg-surface-2 group-hover/l1:text-error group-focus-within/l1:bg-surface-2 group-focus-within/l1:text-error';

interface DropdownItemProps {
    href: ComponentProps<typeof Link>['href'];
    label: string;
    icon?: LucideIcon;
    trailing?: ReactNode;
    hoverClass?: string;
    asButton?: boolean;
    onClick?: () => void;
}

export function DropdownItem({
    href,
    label,
    icon: Icon,
    trailing,
    hoverClass = dropdownItemHover,
    asButton = false,
    onClick,
}: DropdownItemProps) {
    return (
        <Link
            href={href}
            {...(asButton ? { as: 'button' as const } : {})}
            onClick={onClick}
            className={cn(baseClass, hoverClass, asButton && 'w-full text-start')}
        >
            {Icon && <Icon size={18} strokeWidth={1.8} className="shrink-0" aria-hidden="true" />}
            {label}
            {trailing}
        </Link>
    );
}
