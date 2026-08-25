import { Link, usePage } from '@inertiajs/react';
import { HeartIcon, TruckIcon } from 'lucide-react';
import type { RefObject } from 'react';

import * as TrackOrderController from '@/actions/App/Http/Controllers/Storefront/TrackOrderController';
import * as WishlistController from '@/actions/App/Http/Controllers/Storefront/WishlistController';
import { CloseButton } from '@/components/storefront/close-button';
import { MobileMenuAccount } from '@/components/storefront/mobile-menu-account';
import { MobileMenuCategory } from '@/components/storefront/mobile-menu-category';
import { MobileMenuLink } from '@/components/storefront/mobile-menu-link';
import { useMenuAccordion } from '@/hooks/storefront/use-menu-accordion';
import { useOverlay } from '@/hooks/storefront/use-overlay';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { StorefrontMenuItem, StorefrontSharedData } from '@/types';

interface MobileMenuProps {
    open: boolean;
    onClose: () => void;
    menu: StorefrontMenuItem[];
    categories: StorefrontMenuItem[];
}

export function MobileMenu({ open, onClose, menu, categories }: MobileMenuProps) {
    const { auth, wishlist } = usePage<StorefrontSharedData>().props;
    const accordion = useMenuAccordion();

    const close = () => {
        accordion.reset();
        onClose();
    };

    const panelRef = useOverlay(open, close);

    return (
        <div
            className={cn(
                'group fixed inset-0 z-105 lg:hidden',
                open ? 'is-open visible' : 'invisible delay-(--duration-slow)',
            )}
        >
            <div
                onClick={close}
                className={cn(
                    'absolute inset-0 bg-black/40 transition-opacity duration-(--duration-slow) ease-out-quart',
                    open ? 'opacity-100' : 'opacity-0',
                )}
            />
            <aside
                ref={panelRef as RefObject<HTMLElement>}
                role="dialog"
                aria-modal="true"
                aria-labelledby="mobile-menu-title"
                className={cn(
                    'absolute inset-y-0 start-0 flex w-full max-w-[320px] flex-col bg-surface shadow-drawer-start transition-[translate] duration-(--duration-slow) ease-out-quart focus:outline-hidden rtl:shadow-drawer-end',
                    open ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full',
                )}
            >
                <div className="flex h-[68px] shrink-0 items-center justify-between border-b border-line px-5">
                    <h2 id="mobile-menu-title" className="m-0 font-head text-xl font-semibold text-ink">
                        {__('Menu')}
                    </h2>
                    <CloseButton onClick={close} aria-label={__('Close menu')} />
                </div>

                <nav aria-label={__('Mobile menu')} className="no-scrollbar flex-1 overflow-y-auto">
                    <div className="border-b border-line bg-surface-2 py-1">
                        {menu.map((item) => (
                            <Link
                                key={item.label}
                                href={item.url}
                                onClick={close}
                                className="flex h-11 items-center px-5 text-ink transition-colors hover:bg-primary-tint hover:text-primary"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </div>

                    <div className="border-b border-line py-2">
                        <MobileMenuAccount
                            authenticated={Boolean(auth.user)}
                            accordion={accordion}
                            onNavigate={close}
                        />
                        <MobileMenuLink
                            href={WishlistController.show(wishlist.id)}
                            icon={HeartIcon}
                            label={__('Wishlist')}
                            badge={wishlist.product_ids.length}
                            onClick={close}
                        />
                        <MobileMenuLink
                            href={TrackOrderController.create()}
                            icon={TruckIcon}
                            label={__('Track order')}
                            onClick={close}
                        />
                    </div>

                    <div className="py-2">
                        <div className="px-5 pt-3 pb-1.5 text-xs font-bold tracking-label text-muted uppercase">
                            {__('Categories')}
                        </div>
                        {categories.map((node) => (
                            <MobileMenuCategory
                                key={`cat/${node.label}`}
                                node={node}
                                depth={1}
                                itemKey={`cat/${node.label}`}
                                accordion={accordion}
                                onNavigate={close}
                            />
                        ))}
                    </div>
                </nav>
            </aside>
        </div>
    );
}
