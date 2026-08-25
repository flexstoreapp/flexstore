import { Link, router } from '@inertiajs/react';
import { ChevronRightIcon, UserRoundIcon } from 'lucide-react';

import * as AddressController from '@/actions/App/Http/Controllers/Storefront/AddressController';
import * as DashboardController from '@/actions/App/Http/Controllers/Storefront/DashboardController';
import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import * as ProfileController from '@/actions/App/Http/Controllers/Storefront/ProfileController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import { MobileMenuLink } from '@/components/storefront/mobile-menu-link';
import type { MenuAccordion } from '@/hooks/storefront/use-menu-accordion';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const linkClass = 'text-muted transition-colors hover:text-primary flex h-10 items-center pe-5 ps-9 text-sm';

interface MobileMenuAccountProps {
    authenticated: boolean;
    accordion: MenuAccordion;
    onNavigate: () => void;
}

export function MobileMenuAccount({ authenticated, accordion, onNavigate }: MobileMenuAccountProps) {
    if (!authenticated) {
        return (
            <MobileMenuLink
                href={SessionController.create()}
                icon={UserRoundIcon}
                label={__('Log in / Register')}
                onClick={onNavigate}
            />
        );
    }

    const isOpen = accordion.isOpen('account');

    const links = [
        { href: DashboardController.index(), label: __('Dashboard') },
        { href: OrderController.index(), label: __('Orders') },
        { href: AddressController.index(), label: __('Addresses') },
        { href: ProfileController.edit(), label: __('Profile') },
    ];

    return (
        <div>
            <div
                {...accordion.triggerProps('account')}
                className="flex h-11 w-full cursor-default items-center gap-3 px-5 text-ink transition-colors hover:bg-surface-2"
            >
                <UserRoundIcon size={19} strokeWidth={1.6} aria-hidden="true" className="shrink-0 text-muted" />
                {__('Account')}
                <ChevronRightIcon
                    size={12}
                    strokeWidth={2}
                    aria-hidden="true"
                    className={cn(
                        'ms-auto shrink-0 text-muted transition-[rotate] duration-(--duration-fast) ease-out-quart rtl:-scale-x-100',
                        isOpen && 'rotate-90',
                    )}
                />
            </div>
            <div
                className={cn(
                    'grid bg-surface-2 transition-[grid-template-rows] duration-(--duration-base) ease-out-quart [&>*]:overflow-hidden',
                    isOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]',
                )}
            >
                <div>
                    {links.map((link) => (
                        <Link key={link.label} href={link.href} onClick={onNavigate} className={linkClass}>
                            {link.label}
                        </Link>
                    ))}
                    <Link
                        href={SessionController.destroy()}
                        as="button"
                        onClick={() => {
                            onNavigate();
                            router.flushAll();
                        }}
                        className="flex h-10 w-full items-center ps-9 pe-5 text-start text-sm text-muted transition-colors hover:text-error"
                    >
                        {__('Log out')}
                    </Link>
                </div>
            </div>
        </div>
    );
}
