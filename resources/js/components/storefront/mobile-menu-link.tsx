import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import type { ComponentProps } from 'react';

interface MobileMenuLinkProps {
    href: ComponentProps<typeof Link>['href'];
    icon: LucideIcon;
    label: string;
    badge?: number;
    onClick: () => void;
}

export function MobileMenuLink({ href, icon: Icon, label, badge, onClick }: MobileMenuLinkProps) {
    return (
        <Link
            href={href}
            onClick={onClick}
            className="flex h-11 items-center gap-3 px-5 text-ink transition-colors hover:bg-surface-2 hover:text-primary"
        >
            <Icon size={19} strokeWidth={1.6} aria-hidden="true" className="shrink-0 text-muted" />
            {label}
            {badge !== undefined && badge > 0 && (
                <span className="ms-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-primary px-1.5 text-2xs font-bold text-white">
                    {badge}
                </span>
            )}
        </Link>
    );
}
