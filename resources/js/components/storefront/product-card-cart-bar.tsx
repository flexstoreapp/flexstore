import { CheckIcon, CircleAlertIcon } from 'lucide-react';

import { Spinner } from '@/components/storefront/spinner';
import { type useAddToCart } from '@/hooks/storefront/use-add-to-cart';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type Cart = ReturnType<typeof useAddToCart>;

const ctaButtonClass =
    'border-line text-2xs tracking-label flex h-12 flex-1 items-center justify-center gap-1.5 border-t font-semibold uppercase text-white transition-colors focus-visible:border-t-transparent focus-visible:outline focus-visible:-outline-offset-2 focus-visible:outline-white';

const statusBgClass: Record<Cart['status'], string> = {
    idle: 'bg-primary transition-colors can-hover:hover:bg-primary-hover',
    loading: 'bg-primary transition-colors can-hover:hover:bg-primary-hover',
    success: 'bg-success',
    error: 'bg-error',
};

function addToCartContent(status: Cart['status']) {
    if (status === 'loading') {
        return (
            <>
                <Spinner />
                {__('Adding')}
            </>
        );
    }
    if (status === 'success') {
        return (
            <>
                <CheckIcon size={14} aria-hidden="true" />
                {__('Added')}
            </>
        );
    }
    if (status === 'error') {
        return (
            <>
                <CircleAlertIcon size={14} aria-hidden="true" />
                {__('Failed')}
            </>
        );
    }
    return __('Add to cart');
}

interface ProductCardCartBarProps {
    variant: 'select-options' | 'in-stock' | 'out-of-stock';
    reveal: string;
    cart: Cart;
    onAddToCart: () => void;
    onQuickView: () => void;
}

export function ProductCardCartBar({ variant, reveal, cart, onAddToCart, onQuickView }: ProductCardCartBarProps) {
    if (variant === 'out-of-stock') {
        return (
            <div className="absolute inset-x-0 bottom-0 flex">
                <button
                    type="button"
                    disabled
                    className="flex h-12 flex-1 cursor-not-allowed items-center justify-center bg-surface-2 text-2xs font-semibold tracking-label text-muted uppercase"
                >
                    {__('Out of stock')}
                </button>
            </div>
        );
    }

    return (
        <div
            className={cn(
                'absolute inset-x-0 bottom-0 flex translate-y-full flex-wrap opacity-0 transition-[opacity,translate] duration-(--duration-fast) ease-out-quart no-hover:hidden',
                reveal,
            )}
        >
            {variant === 'select-options' ? (
                <button
                    type="button"
                    onClick={onQuickView}
                    className={cn(ctaButtonClass, 'bg-primary transition-colors can-hover:hover:bg-primary-hover')}
                >
                    {__('Select options')}
                </button>
            ) : (
                <>
                    <p
                        role="alert"
                        className={cn(
                            'order-first flex w-full basis-full items-start gap-1.5 overflow-hidden bg-error px-3 text-2xs leading-snug text-white transition-[max-height,padding,opacity] duration-(--duration-fast) ease-out-quart',
                            cart.status === 'error' ? 'max-h-24 py-2 opacity-100' : 'max-h-0 py-0 opacity-0',
                        )}
                    >
                        {cart.error}
                    </p>
                    <button
                        type="button"
                        onClick={onAddToCart}
                        disabled={cart.status === 'loading' || cart.status === 'error'}
                        className={cn(
                            ctaButtonClass,
                            statusBgClass[cart.status],
                            cart.status === 'loading' && 'pointer-events-none cursor-wait',
                        )}
                    >
                        {addToCartContent(cart.status)}
                    </button>
                </>
            )}
        </div>
    );
}
