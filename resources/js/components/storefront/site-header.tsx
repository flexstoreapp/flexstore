import { Link, usePage } from '@inertiajs/react';
import { HeartIcon, MenuIcon, SearchIcon, ShoppingCartIcon } from 'lucide-react';
import { useState } from 'react';

import * as WishlistController from '@/actions/App/Http/Controllers/Storefront/WishlistController';
import { useFormatMoney } from '@/hooks/use-format-money';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontSharedData } from '@/types';

import { HeaderSearch } from './header-search';
import { Logo } from './logo';

function Badge({ count }: { count: number }) {
    if (count <= 0) {
        return null;
    }

    return (
        <span
            aria-hidden="true"
            className="absolute -end-2 -top-1.5 flex h-[17px] w-[17px] items-center justify-center rounded-full bg-primary text-2xs font-bold text-white"
        >
            {count}
        </span>
    );
}

interface SiteHeaderProps {
    compact?: boolean;
    onOpenMobileMenu: () => void;
    onOpenCart: () => void;
}

export function SiteHeader({ compact = false, onOpenMobileMenu, onOpenCart }: SiteHeaderProps) {
    const { cart, wishlist } = usePage<StorefrontSharedData>().props;
    const { formatMoney } = useFormatMoney();
    const [searchOpen, setSearchOpen] = useState(false);

    const cartCount = cart.items?.reduce((total, item) => total + item.quantity, 0) ?? 0;

    return (
        <header className="border-b border-line bg-surface">
            <div
                className={cn(
                    'mx-auto flex w-full max-w-page flex-wrap items-center gap-x-6 gap-y-3 px-6 py-4 lg:grid lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:gap-8 lg:py-0',
                    compact ? 'lg:h-16' : 'lg:h-[88px]',
                )}
            >
                <button
                    type="button"
                    onClick={onOpenMobileMenu}
                    className="-ms-1 rounded-sm text-ink lg:hidden"
                    aria-label={__('Open menu')}
                    aria-haspopup="dialog"
                >
                    <MenuIcon size={26} strokeWidth={2} aria-hidden="true" />
                </button>

                <Logo />

                <div
                    className={cn(
                        'order-3 w-full lg:order-none lg:mx-auto lg:max-w-[760px]',
                        searchOpen ? 'block' : 'hidden lg:block',
                    )}
                >
                    <HeaderSearch active={searchOpen} className="w-full" />
                </div>

                <div className="ms-auto flex items-center gap-4 sm:gap-7 lg:justify-self-end">
                    <button
                        type="button"
                        onClick={() => setSearchOpen((open) => !open)}
                        aria-expanded={searchOpen}
                        className="-m-1 p-1 text-ink lg:hidden"
                        aria-label={__('Search')}
                    >
                        <SearchIcon size={23} strokeWidth={2} aria-hidden="true" />
                    </button>

                    <Link
                        href={WishlistController.show(wishlist.id)}
                        className="relative hidden text-ink hover:opacity-80 lg:block"
                        aria-label={__('Wishlist')}
                    >
                        <HeartIcon size={24} strokeWidth={1.6} aria-hidden="true" />
                        <Badge count={wishlist.product_ids.length} />
                    </Link>

                    <button
                        type="button"
                        onClick={onOpenCart}
                        className="flex items-center gap-3 text-ink"
                        aria-label={__('Open cart')}
                    >
                        <span className="relative">
                            <ShoppingCartIcon size={25} strokeWidth={1.6} aria-hidden="true" />
                            <Badge count={cartCount} />
                        </span>
                        <span className="hidden text-start sm:block">
                            <span className="block text-muted">{__('My cart')}</span>
                            <span className="block font-semibold text-ink">{formatMoney(cart.total)}</span>
                        </span>
                    </button>
                </div>
            </div>
        </header>
    );
}
