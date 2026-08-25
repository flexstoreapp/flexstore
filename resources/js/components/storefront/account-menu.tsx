import { Link, router, usePage } from '@inertiajs/react';
import { ChevronDownIcon, LayoutDashboardIcon, LogOutIcon, MapPinIcon, PackageIcon, UserRoundIcon } from 'lucide-react';

import * as AddressController from '@/actions/App/Http/Controllers/Storefront/AddressController';
import * as DashboardController from '@/actions/App/Http/Controllers/Storefront/DashboardController';
import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import * as ProfileController from '@/actions/App/Http/Controllers/Storefront/ProfileController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import { useFlyoutDismiss } from '@/hooks/storefront/use-flyout-dismiss';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontSharedData } from '@/types';

import { DropdownItem, dropdownItemHoverDestructive } from './dropdown-item';
import { FLYOUT_PANEL_EDGES_BOTTOM, FLYOUT_TRANSITION } from './flyout';

export function AccountMenu() {
    const { auth } = usePage<StorefrontSharedData>().props;
    const { ref, onClickCapture } = useFlyoutDismiss<HTMLDivElement>();

    if (!auth.user) {
        return (
            <Link
                href={SessionController.create()}
                className="hidden h-9 items-center transition-colors hover:text-white lg:flex"
            >
                {__('Log in / Register')}
            </Link>
        );
    }

    const links = [
        { href: DashboardController.index(), label: __('Dashboard'), icon: LayoutDashboardIcon },
        { href: OrderController.index(), label: __('Orders'), icon: PackageIcon },
        { href: AddressController.index(), label: __('Addresses'), icon: MapPinIcon },
        { href: ProfileController.edit(), label: __('Profile'), icon: UserRoundIcon },
    ];

    return (
        <div ref={ref} onClickCapture={onClickCapture} className="group/acct relative hidden h-9 items-center lg:flex">
            <button
                type="button"
                className="flex h-full items-center gap-1 transition-colors group-hover/acct:text-white hover:text-white focus-visible:text-white"
                aria-haspopup="true"
            >
                {__('Account')}
                <ChevronDownIcon size={12} strokeWidth={2.2} aria-hidden="true" />
            </button>
            <div
                className={cn(
                    FLYOUT_TRANSITION,
                    'start-0 text-ink group-focus-within/acct:visible group-focus-within/acct:scale-y-100 group-focus-within/acct:opacity-100 group-hover/acct:visible group-hover/acct:scale-y-100 group-hover/acct:opacity-100 group-hover/acct:delay-60 group-hover/acct:duration-(--duration-fast)',
                )}
            >
                <div
                    className={cn(
                        'w-55 rounded-b-md border border-line bg-surface shadow-md',
                        FLYOUT_PANEL_EDGES_BOTTOM,
                    )}
                >
                    {links.map((link) => (
                        <div key={link.label} className="group/l1 relative">
                            <DropdownItem href={link.href} label={link.label} icon={link.icon} />
                        </div>
                    ))}
                    <div className="group/l1 relative">
                        <DropdownItem
                            href={SessionController.destroy()}
                            label={__('Log out')}
                            icon={LogOutIcon}
                            asButton
                            onClick={() => router.flushAll()}
                            hoverClass={dropdownItemHoverDestructive}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
