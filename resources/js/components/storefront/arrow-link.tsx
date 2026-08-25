import { Link } from '@inertiajs/react';
import { ArrowLeftIcon, ArrowRightIcon } from 'lucide-react';
import type { ComponentProps, ReactNode } from 'react';

import { cn } from '@/lib/utils';

interface ArrowLinkProps {
    href: ComponentProps<typeof Link>['href'];
    children: ReactNode;
    direction?: 'forward' | 'back';
    size?: 'default' | 'sm';
    onClick?: () => void;
    className?: string;
}

export function ArrowLink({
    href,
    children,
    direction = 'forward',
    size = 'default',
    onClick,
    className,
}: ArrowLinkProps) {
    const ArrowIcon = direction === 'back' ? ArrowLeftIcon : ArrowRightIcon;
    const arrow = (
        <ArrowIcon size={size === 'sm' ? 14 : 15} strokeWidth={2} aria-hidden="true" className="rtl:-scale-x-100" />
    );

    return (
        <Link
            href={href}
            onClick={onClick}
            className={cn(
                'inline-flex items-center gap-1 font-semibold text-muted transition-colors hover:text-primary',
                size === 'sm' && 'text-sm',
                className,
            )}
        >
            {direction === 'back' && arrow}
            {children}
            {direction === 'forward' && arrow}
        </Link>
    );
}
