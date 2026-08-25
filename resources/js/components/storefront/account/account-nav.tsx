import { Form, Link, usePage } from '@inertiajs/react';
import { LayoutDashboardIcon, LogOutIcon, MapPinIcon, PackageIcon, UserRoundIcon, type LucideIcon } from 'lucide-react';
import { useEffect, useRef } from 'react';

import * as AddressController from '@/actions/App/Http/Controllers/Storefront/AddressController';
import * as DashboardController from '@/actions/App/Http/Controllers/Storefront/DashboardController';
import * as OrderController from '@/actions/App/Http/Controllers/Storefront/OrderController';
import * as ProfileController from '@/actions/App/Http/Controllers/Storefront/ProfileController';
import * as SessionController from '@/actions/App/Http/Controllers/Storefront/SessionController';
import { ScrollFade } from '@/components/scroll-fade';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontSharedData } from '@/types';

interface AccountNavItem {
    label: string;
    href: string;
    icon: LucideIcon;
}

const NAV_ITEMS: AccountNavItem[] = [
    { label: __('Dashboard'), href: DashboardController.index().url, icon: LayoutDashboardIcon },
    { label: __('Orders'), href: OrderController.index().url, icon: PackageIcon },
    { label: __('Addresses'), href: AddressController.index().url, icon: MapPinIcon },
    { label: __('Profile'), href: ProfileController.edit().url, icon: UserRoundIcon },
];

function useActivePath(): (href: string) => boolean {
    const { url } = usePage();
    const currentPath = url.split('?')[0];

    return (href: string) => {
        const matches = currentPath === href || currentPath.startsWith(`${href}/`);

        if (!matches) {
            return false;
        }

        return !NAV_ITEMS.some(
            (item) => item.href !== href && item.href.length > href.length && currentPath.startsWith(item.href),
        );
    };
}

export function AccountNav() {
    const isActive = useActivePath();
    const activeTabRef = useRef<HTMLAnchorElement | null>(null);
    const { url, props } = usePage<StorefrontSharedData>();
    const stickyHeader = props.storefront.header.sticky;

    useEffect(() => {
        const tab = activeTabRef.current;
        const scroller = tab?.closest<HTMLElement>('.scroll-fade-scroller');

        if (!tab || !scroller) {
            return;
        }

        const tabRect = tab.getBoundingClientRect();
        const scrollerRect = scroller.getBoundingClientRect();
        const fullyVisible = tabRect.left >= scrollerRect.left && tabRect.right <= scrollerRect.right;

        if (fullyVisible) {
            return;
        }

        scroller.scrollLeft += tabRect.left - scrollerRect.left - (scroller.clientWidth - tab.offsetWidth) / 2;
    }, [url]);

    return (
        <aside className={cn('min-w-0 lg:sticky', stickyHeader ? 'lg:top-22' : 'lg:top-6')}>
            <ScrollFade
                axis="x"
                className="[--scroll-fade-color:var(--color-page)] lg:hidden"
                scrollerClassName="no-scrollbar overflow-x-auto"
            >
                <nav aria-label={__('Account')} className="flex w-max gap-2 pb-px">
                    {NAV_ITEMS.map((item) => (
                        <Link
                            key={item.href}
                            ref={isActive(item.href) ? activeTabRef : undefined}
                            href={item.href}
                            aria-current={isActive(item.href) ? 'page' : undefined}
                            className={cn(
                                'inline-flex h-10 shrink-0 items-center gap-2 rounded-md border px-4 text-sm font-semibold whitespace-nowrap transition-colors',
                                isActive(item.href)
                                    ? 'border-primary bg-primary-tint text-primary'
                                    : 'border-line-strong bg-surface text-body can-hover:hover:border-primary can-hover:hover:text-primary',
                            )}
                        >
                            <item.icon size={18} strokeWidth={1.8} aria-hidden="true" className="shrink-0" />
                            {item.label}
                        </Link>
                    ))}
                    <LogoutButton variant="tab" />
                </nav>
            </ScrollFade>

            <nav
                aria-label={__('Account')}
                className="hidden overflow-hidden rounded-md border border-line bg-surface text-ink lg:block"
            >
                {NAV_ITEMS.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        aria-current={isActive(item.href) ? 'page' : undefined}
                        className={cn(
                            'flex h-[46px] items-center gap-3 border-b border-line px-5 text-base transition-colors',
                            isActive(item.href)
                                ? 'bg-surface-2 font-semibold text-primary'
                                : 'text-body can-hover:hover:text-primary',
                        )}
                    >
                        <item.icon size={18} strokeWidth={1.8} aria-hidden="true" className="shrink-0" />
                        {item.label}
                    </Link>
                ))}
                <LogoutButton variant="row" />
            </nav>
        </aside>
    );
}

function LogoutButton({ variant }: { variant: 'tab' | 'row' }) {
    return (
        <Form {...SessionController.destroy.form()} className={variant === 'tab' ? 'shrink-0' : undefined}>
            <button
                type="submit"
                className={cn(
                    'flex items-center transition-colors',
                    variant === 'tab'
                        ? 'h-10 gap-2 rounded-md border border-line-strong bg-surface px-4 text-sm font-semibold whitespace-nowrap text-body can-hover:hover:border-error can-hover:hover:text-error'
                        : 'h-[46px] w-full gap-3 px-5 text-start text-base text-body can-hover:hover:text-error',
                )}
            >
                <LogOutIcon size={18} strokeWidth={1.8} aria-hidden="true" className="shrink-0 rtl:-scale-x-100" />
                {__('Log out')}
            </button>
        </Form>
    );
}
