import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';

import { CartDrawer } from '@/components/storefront/cart-drawer';
import { ConfirmProvider } from '@/components/storefront/confirm';
import { MobileMenu } from '@/components/storefront/mobile-menu';
import { NavBar } from '@/components/storefront/nav-bar';
import { QuickViewProvider } from '@/components/storefront/quick-view-context';
import { SiteFooter } from '@/components/storefront/site-footer';
import { SiteHeader } from '@/components/storefront/site-header';
import { TopBar } from '@/components/storefront/top-bar';
import { usePointerModality } from '@/hooks/storefront/use-pointer-modality';
import { useResizeGuard } from '@/hooks/storefront/use-resize-guard';
import { cn } from '@/lib/utils';
import type { StorefrontLayout as StorefrontLayoutData } from '@/types';

let customJsInjected = false;

interface StorefrontSharedProps {
    storefront: StorefrontLayoutData;
    [key: string]: unknown;
}

export function StorefrontLayout({ children }: { children: ReactNode }) {
    const { storefront } = usePage<StorefrontSharedProps>().props;
    const { sticky } = storefront.header;
    const [cartOpen, setCartOpen] = useState(false);
    const [menuOpen, setMenuOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    useResizeGuard();
    usePointerModality();

    useEffect(() => {
        if (!storefront.customJs || customJsInjected) {
            return;
        }

        customJsInjected = true;
        const script = document.createElement('script');
        script.textContent = storefront.customJs;
        document.body.appendChild(script);
    }, [storefront.customJs]);

    useEffect(() => {
        if (!sticky) {
            return;
        }

        const onScroll = () => setScrolled(window.scrollY > 200);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, [sticky]);

    return (
        <ConfirmProvider>
            <div className="flex min-h-svh flex-col">
                {storefront.customCss && (
                    <Head>
                        <style>{storefront.customCss}</style>
                    </Head>
                )}

                <TopBar announcements={storefront.announcements} />
                <SiteHeader onOpenMobileMenu={() => setMenuOpen(true)} onOpenCart={() => setCartOpen(true)} />
                <NavBar menu={storefront.headerMenu} categories={storefront.browseCategories} />

                {sticky && (
                    <div
                        className={cn(
                            'fixed inset-x-0 top-0 z-40 shadow-2xs transition-[translate,visibility] duration-(--duration-slow) ease-out-quart',
                            scrolled ? 'visible translate-y-0' : 'invisible -translate-y-full',
                        )}
                    >
                        <SiteHeader
                            compact
                            onOpenMobileMenu={() => setMenuOpen(true)}
                            onOpenCart={() => setCartOpen(true)}
                        />
                    </div>
                )}

                <QuickViewProvider>
                    <main className="flex-1">{children}</main>
                </QuickViewProvider>

                <SiteFooter />
            </div>

            <MobileMenu
                open={menuOpen}
                onClose={() => setMenuOpen(false)}
                menu={storefront.headerMenu}
                categories={storefront.browseCategories}
            />
            <CartDrawer open={cartOpen} onClose={() => setCartOpen(false)} />
        </ConfirmProvider>
    );
}
