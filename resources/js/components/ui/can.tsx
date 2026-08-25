import type { ReactNode } from 'react';

import { usePermissions } from '@/hooks/admin/use-permissions';
import type { Permission } from '@/lib/permissions';

type SinglePermissionProps = {
    permission: Permission;
};

type MultiplePermissionsProps = {
    permissions: Permission[];
    requireAll?: boolean;
};

type CanProps = (SinglePermissionProps | MultiplePermissionsProps) & {
    fallback?: ReactNode;
};

export function Can({ fallback = null, children, ...props }: React.PropsWithChildren<CanProps>) {
    const { hasPermission, hasAnyPermission, hasAllPermissions } = usePermissions();

    let hasAccess = false;

    if ('permission' in props) {
        hasAccess = hasPermission(props.permission);
    } else if (props.requireAll) {
        hasAccess = hasAllPermissions(props.permissions);
    } else {
        hasAccess = hasAnyPermission(props.permissions);
    }

    return hasAccess ? children : fallback;
}
