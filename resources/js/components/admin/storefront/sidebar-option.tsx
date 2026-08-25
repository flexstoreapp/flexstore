import { type UrlMethodPair } from '@inertiajs/core';
import { Link } from '@inertiajs/react';

import { ProBadge } from '@/components/admin/pro/pro-badge';
import { useProUpgrade } from '@/components/admin/pro/pro-upgrade-context';
import { cn } from '@/lib/utils';

interface SidebarOptionProps {
    title: string;
    description: string;
    icon: React.ReactNode;
    href?: UrlMethodPair;
    pro?: boolean;
}

export function SidebarOption({ title, description, icon, href, pro = false }: SidebarOptionProps) {
    const { open: openProUpgrade } = useProUpgrade();

    if (pro || !href) {
        return (
            <button
                type="button"
                onClick={() => openProUpgrade(title)}
                className={cn(
                    'flex w-full cursor-default items-center gap-2 rounded-lg p-3 text-start transition-colors duration-150 hover:bg-muted dark:hover:bg-muted/50',
                    'outline-none focus-visible:outline-none',
                    'focus-visible:ring-2 focus-visible:ring-primary',
                    'focus-visible:ring-offset-2 focus-visible:ring-offset-background',
                )}
            >
                <div className="flex size-10 items-center justify-center opacity-60">{icon}</div>
                <div className="min-w-0 flex-1 space-y-0.5">
                    <p className="flex items-center gap-2 text-sm font-medium">
                        {title}
                        <ProBadge />
                    </p>
                    <p className="truncate text-xs text-muted-foreground">{description}</p>
                </div>
            </button>
        );
    }

    return (
        <Link
            href={href}
            prefetch
            className={cn(
                'flex items-center gap-2 rounded-lg p-3 transition-colors duration-150 hover:bg-muted dark:hover:bg-muted/50',
                'outline-none focus-visible:outline-none',
                'focus-visible:ring-2 focus-visible:ring-primary',
                'focus-visible:ring-offset-2 focus-visible:ring-offset-background',
            )}
        >
            <div className="flex size-10 items-center justify-center">{icon}</div>
            <div className="space-y-0.5">
                <p className="text-sm font-medium">{title}</p>
                <p className="text-xs text-muted-foreground">{description}</p>
            </div>
        </Link>
    );
}
