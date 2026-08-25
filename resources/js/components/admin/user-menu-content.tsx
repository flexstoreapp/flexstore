import { Link, router } from '@inertiajs/react';
import { LogOutIcon, UserIcon } from 'lucide-react';

import * as ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import * as SessionController from '@/actions/App/Http/Controllers/Admin/SessionController';
import { UserInfo } from '@/components/admin/user-info';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { useMobileNavigation } from '@/hooks/admin/use-mobile-navigation';
import { __ } from '@/lib/i18n';
import type { User } from '@/types';

interface UserMenuContentProps {
    user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
    const cleanup = useMobileNavigation();

    const handleLogout = () => {
        cleanup();
        router.flushAll();
    };

    return (
        <>
            <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                    <UserInfo user={user} showEmail={true} />
                </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuGroup>
                <DropdownMenuItem asChild>
                    <Link
                        className="block w-full"
                        href={ProfileController.edit()}
                        as="button"
                        prefetch
                        onClick={cleanup}
                    >
                        <UserIcon />
                        {__('Account')}
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
                <Link
                    className="block w-full"
                    method="post"
                    href={SessionController.destroy()}
                    as="button"
                    onClick={handleLogout}
                >
                    <LogOutIcon className="rtl:-scale-x-100" />
                    {__('Log out')}
                </Link>
            </DropdownMenuItem>
        </>
    );
}
