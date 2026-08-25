import { Link, usePage } from '@inertiajs/react';
import { CircleAlertIcon, ShoppingCartIcon, Trash2Icon } from 'lucide-react';
import { useEffect, useRef } from 'react';

import * as CartController from '@/actions/App/Http/Controllers/Storefront/CartController';
import * as CheckoutController from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import * as ProductController from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { buttonVariants } from '@/components/storefront/button';
import { CloseButton } from '@/components/storefront/close-button';
import { ContainedMedia } from '@/components/storefront/contained-media';
import { QuantityStepper } from '@/components/storefront/quantity-stepper';
import { useCartItem } from '@/hooks/storefront/use-cart-item';
import { useOverlay } from '@/hooks/storefront/use-overlay';
import { useFormatMoney } from '@/hooks/use-format-money';
import { analytics } from '@/lib/analytics';
import { __ } from '@/lib/i18n';
import { lineItemMedia } from '@/lib/media';
import { cn, getTranslation } from '@/lib/utils';
import type { CartItem, StorefrontSharedData } from '@/types';

function LineItem({ item, onNavigate }: { item: CartItem; onNavigate: () => void }) {
    const { formatMoney } = useFormatMoney();
    const cartItem = useCartItem(item);

    const product = item.product;
    const title = product ? getTranslation(product.title) : '';
    const href = product ? ProductController.show(product.url_handle) : null;

    const thumb = <ContainedMedia media={lineItemMedia(item)} alt={title} source="small" />;

    const thumbClass = 'bg-surface-2 relative block size-[76px] shrink-0 overflow-hidden rounded-md';

    return (
        <div className="flex items-start gap-4 py-5">
            {href ? (
                <Link href={href} tabIndex={-1} aria-hidden="true" onClick={onNavigate} className={thumbClass}>
                    {thumb}
                </Link>
            ) : (
                <span className={thumbClass}>{thumb}</span>
            )}
            <div className="min-w-0 flex-1">
                <div className="flex items-start justify-between gap-3">
                    {href ? (
                        <Link
                            href={href}
                            onClick={onNavigate}
                            className="line-clamp-2 leading-snug font-semibold text-ink transition-colors hover:text-primary"
                        >
                            {title}
                        </Link>
                    ) : (
                        <span className="line-clamp-2 leading-snug font-semibold text-ink">{title}</span>
                    )}
                    <span className="shrink-0 font-bold text-ink">{formatMoney(item.total_price)}</span>
                </div>
                {item.variant_title && <div className="mt-0.5 truncate text-sm text-muted">{item.variant_title}</div>}
                <div className="mt-2.5 flex items-center justify-between">
                    <QuantityStepper
                        value={cartItem.quantity}
                        onChange={cartItem.setQuantity}
                        disabled={cartItem.updating}
                        size="sm"
                    />
                    <button
                        type="button"
                        aria-label={__('Remove :name', { name: title })}
                        disabled={cartItem.removing}
                        onClick={cartItem.remove}
                        className="-me-px flex size-8 items-center justify-end rounded-xs text-muted transition disabled:opacity-50 can-hover:hover:text-error"
                    >
                        <Trash2Icon size={16} aria-hidden="true" />
                    </button>
                </div>
                {cartItem.error && (
                    <p role="alert" className="mt-1.5 flex items-center gap-1.5 text-sm text-error">
                        <CircleAlertIcon size={14} aria-hidden="true" />
                        {cartItem.error}
                    </p>
                )}
            </div>
        </div>
    );
}

export function CartDrawer({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { cart, activeCurrency } = usePage<StorefrontSharedData>().props;
    const { formatMoney } = useFormatMoney();
    const panelRef = useOverlay(open, onClose);

    const items = cart.items ?? [];
    const count = items.reduce((total, item) => total + item.quantity, 0);

    const viewCartData = useRef({ cart, activeCurrency });
    useEffect(() => {
        viewCartData.current = { cart, activeCurrency };
    });
    useEffect(() => {
        if (open && (viewCartData.current.cart.items?.length ?? 0) > 0) {
            analytics.viewCart(viewCartData.current.cart, viewCartData.current.activeCurrency);
        }
    }, [open]);

    return (
        <div
            className={cn('group fixed inset-0 z-100', open ? 'is-open visible' : 'invisible delay-(--duration-slow)')}
        >
            <div
                onClick={onClose}
                className={cn(
                    'absolute inset-0 bg-black/40 transition-opacity duration-(--duration-slow) ease-out-quart',
                    open ? 'opacity-100' : 'opacity-0',
                )}
            />
            <aside
                ref={panelRef as React.RefObject<HTMLElement>}
                role="dialog"
                aria-modal="true"
                aria-labelledby="cart-drawer-title"
                className={cn(
                    'absolute inset-y-0 end-0 flex w-full max-w-[420px] flex-col bg-surface shadow-drawer-end transition-[translate,opacity] duration-(--duration-slow) ease-out-quart focus:outline-hidden max-[30rem]:max-w-none rtl:shadow-drawer-start',
                    open ? 'translate-x-0' : 'translate-x-full rtl:-translate-x-full',
                )}
            >
                <div className="flex h-[68px] shrink-0 items-center justify-between border-b border-line px-5 sm:px-6">
                    <h2 id="cart-drawer-title" className="m-0 font-head text-xl font-semibold text-ink">
                        {__('Shopping cart')} ({count})
                    </h2>
                    <CloseButton onClick={onClose} aria-label={__('Close cart')} />
                </div>

                {items.length > 0 ? (
                    <>
                        <div className="no-scrollbar flex-1 divide-y divide-line overflow-y-auto px-5 py-2 sm:px-6">
                            {items.map((item) => (
                                <LineItem key={item.id} item={item} onNavigate={onClose} />
                            ))}
                        </div>
                        <div className="shrink-0 border-t border-line px-5 py-5 sm:px-6">
                            <div className="flex items-center justify-between font-head text-lg font-bold text-ink">
                                <span>{__('Subtotal')}</span>
                                <span>{formatMoney(cart.subtotal)}</span>
                            </div>
                            <Link
                                href={CartController.show()}
                                onClick={onClose}
                                className={cn(buttonVariants({ variant: 'outline', size: 'lg', block: true }), 'mt-4')}
                                prefetch
                            >
                                {__('View cart')}
                            </Link>
                            <Link
                                href={CheckoutController.create()}
                                onClick={onClose}
                                className={cn(buttonVariants({ size: 'lg', block: true }), 'mt-2')}
                            >
                                {__('Checkout')}
                            </Link>
                        </div>
                    </>
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center gap-5 px-6 pb-28 text-center">
                        <div className="flex size-20 items-center justify-center rounded-full bg-surface-2 text-muted">
                            <ShoppingCartIcon size={34} strokeWidth={1.5} aria-hidden="true" />
                        </div>
                        <h3 className="font-head text-3xl font-semibold text-ink">{__('Your cart is empty')}</h3>
                    </div>
                )}
            </aside>
        </div>
    );
}
