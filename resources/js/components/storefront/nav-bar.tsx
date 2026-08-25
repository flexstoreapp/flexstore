import { Link } from '@inertiajs/react';
import { ChevronDownIcon, MenuIcon } from 'lucide-react';

import { useFlyoutDismiss } from '@/hooks/storefront/use-flyout-dismiss';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontMenuItem } from '@/types';

import { BrowseCategoryMegaMenu } from './browse-category-mega-menu';
import { FLYOUT_PANEL_EDGES_BOTTOM, FLYOUT_TRANSITION } from './flyout';
import { MenuItem, type MenuItemData } from './menu-item';
import { NavMegaMenu } from './nav-mega-menu';

const toMenuItem = (item: StorefrontMenuItem): MenuItemData => ({
    label: item.label,
    href: item.url,
    children: item.children?.map(toMenuItem),
});

function PrimaryItem({ item }: { item: StorefrontMenuItem }) {
    if (item.is_mega_menu && item.children?.length) {
        return (
            <div className="group/mega flex h-13 items-center">
                <Link href={item.url} className="flex h-full items-center gap-1.5 group-hover/mega:text-primary">
                    {item.label}
                    <ChevronDownIcon size={12} strokeWidth={2.2} aria-hidden="true" />
                </Link>
                <NavMegaMenu item={item} />
            </div>
        );
    }

    if (item.children?.length) {
        return (
            <div className="group/dd relative flex h-13 items-center">
                <Link
                    href={item.url}
                    className="flex items-center gap-1 transition-colors group-hover/dd:text-primary hover:text-primary"
                >
                    {item.label}
                    <ChevronDownIcon size={13} strokeWidth={2.2} aria-hidden="true" />
                </Link>
                <div
                    className={cn(
                        FLYOUT_TRANSITION,
                        'start-0 w-60 rounded-b-md border border-line bg-surface font-normal text-ink shadow-md group-focus-within/dd:visible group-focus-within/dd:scale-y-100 group-focus-within/dd:opacity-100 group-hover/dd:visible group-hover/dd:scale-y-100 group-hover/dd:opacity-100 group-hover/dd:delay-60 group-hover/dd:duration-(--duration-fast)',
                        FLYOUT_PANEL_EDGES_BOTTOM,
                    )}
                >
                    {item.children.map((child) => (
                        <MenuItem key={child.label} {...toMenuItem(child)} />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <Link href={item.url} className="flex h-[52px] items-center transition-colors hover:text-primary">
            {item.label}
        </Link>
    );
}

export function NavBar({ menu, categories }: { menu: StorefrontMenuItem[]; categories: StorefrontMenuItem[] }) {
    const { ref, onClickCapture } = useFlyoutDismiss<HTMLDivElement>();

    if (menu.length === 0 && categories.length === 0) {
        return null;
    }

    return (
        <div
            ref={ref}
            onClickCapture={onClickCapture}
            className="relative z-30 hidden border-b border-line bg-surface lg:block"
        >
            <div className="mx-auto flex w-full max-w-page items-center px-6">
                {categories.length > 0 && (
                    <div className="group relative">
                        <button
                            type="button"
                            className="flex h-13 w-67 items-center gap-3 bg-primary px-5 font-semibold tracking-wide text-white uppercase"
                            aria-haspopup="true"
                        >
                            <MenuIcon size={18} strokeWidth={2} aria-hidden="true" />
                            {__('Browse categories')}
                            <ChevronDownIcon size={14} strokeWidth={2} aria-hidden="true" className="ms-auto" />
                        </button>

                        <div
                            className={cn(
                                FLYOUT_TRANSITION,
                                'start-0 w-[270px] rounded-b-md border border-line bg-surface shadow-md group-focus-within:visible group-focus-within:scale-y-100 group-focus-within:opacity-100 group-hover:visible group-hover:scale-y-100 group-hover:opacity-100 group-hover:delay-60 group-hover:duration-(--duration-fast)',
                                FLYOUT_PANEL_EDGES_BOTTOM,
                            )}
                        >
                            {categories.map((category) =>
                                category.is_mega_menu && category.children?.length ? (
                                    <MenuItem
                                        key={category.label}
                                        label={category.label}
                                        href={category.url}
                                        submenu={<BrowseCategoryMegaMenu category={category} />}
                                    />
                                ) : (
                                    <MenuItem key={category.label} {...toMenuItem(category)} />
                                ),
                            )}
                        </div>
                    </div>
                )}

                <nav
                    aria-label={__('Primary')}
                    className={cn('flex items-center gap-8 font-semibold text-ink', categories.length > 0 && 'ms-10')}
                >
                    {menu.map((item) => (
                        <PrimaryItem key={item.label} item={item} />
                    ))}
                </nav>
            </div>
        </div>
    );
}
