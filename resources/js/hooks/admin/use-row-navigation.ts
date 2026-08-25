import { router } from '@inertiajs/react';

import type { Permission } from '@/lib/permissions';

import { usePermissions } from './use-permissions';

interface UseRowNavigationOptions<T = void> {
    url: T extends void ? string : (entity: T) => string;
    permission: Permission;
}

export function useRowNavigation<T = void>({ url, permission }: UseRowNavigationOptions<T>) {
    const { hasPermission } = usePermissions();
    const canNavigate = hasPermission(permission);

    const handleRowClick = (e: React.MouseEvent, entity?: T) => {
        if (!canNavigate || e.target instanceof HTMLAnchorElement) {
            return;
        }

        const targetUrl: string = typeof url === 'function' ? url(entity as T) : url;

        if (e.metaKey || e.ctrlKey) {
            window.open(targetUrl, '_blank');
            return;
        }

        router.visit(targetUrl);
    };

    const handleLinkClick = (e: React.MouseEvent) => {
        if (e.metaKey || e.ctrlKey) {
            e.stopPropagation();
        }
    };

    return {
        canNavigate,
        handleRowClick,
        handleLinkClick,
    };
}
