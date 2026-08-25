import { usePage } from '@inertiajs/react';

import { type Permission, Role } from '@/lib/permissions';
import type { AdminSharedData } from '@/types';

export function usePermissions() {
    const { auth } = usePage<AdminSharedData>().props;
    const userPermissions = auth.permissions.map((p) => p.name);

    const isSuperAdmin = () => {
        return auth.roles.some((r) => r.name === Role.SuperAdmin) ?? false;
    };

    const hasPermission = (permission: Permission) => {
        if (!auth.user) return false;
        if (isSuperAdmin()) return true;
        return userPermissions.includes(permission);
    };

    const hasAnyPermission = (permissions: Permission[]) => {
        if (!auth.user) return false;
        if (isSuperAdmin()) return true;
        return permissions.some((permission) => userPermissions.includes(permission));
    };

    const hasAllPermissions = (permissions: Permission[]) => {
        if (!auth.user) return false;
        if (isSuperAdmin()) return true;
        return permissions.every((permission) => userPermissions.includes(permission));
    };

    return {
        isSuperAdmin,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
    };
}
