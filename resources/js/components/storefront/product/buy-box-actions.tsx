import { CheckIcon, CircleAlertIcon, ShoppingCartIcon } from 'lucide-react';

import { Button } from '@/components/storefront/button';
import { Spinner } from '@/components/storefront/spinner';
import type { ProductPurchase } from '@/hooks/storefront/use-product-purchase';
import { __ } from '@/lib/i18n';

interface BuyBoxActionsProps {
    cart: ProductPurchase['cart'];
    label: string;
    canPurchase: boolean;
    onAddToCart: () => void;
    onBuyNow: () => void;
    size?: 'default' | 'lg';
}

const SIZES = {
    default: { button: undefined, icon: 18, spinner: 'size-4.5', addClass: 'flex-1' },
    lg: { button: 'lg', icon: 20, spinner: 'size-5', addClass: 'flex-1 gap-2.5' },
} as const;

export function BuyBoxActions({
    cart,
    label,
    canPurchase,
    onAddToCart,
    onBuyNow,
    size = 'default',
}: BuyBoxActionsProps) {
    const styles = SIZES[size];

    return (
        <>
            <div className="mt-5 flex gap-3">
                <Button
                    size={styles.button}
                    variant={cart.status === 'success' ? 'success' : cart.status === 'error' ? 'error' : 'primary'}
                    disabled={!canPurchase}
                    onClick={onAddToCart}
                    className={styles.addClass}
                >
                    {cart.status === 'loading' ? (
                        <Spinner className={styles.spinner} />
                    ) : cart.status === 'success' ? (
                        <CheckIcon size={styles.icon} aria-hidden="true" />
                    ) : cart.status === 'error' ? (
                        <CircleAlertIcon size={styles.icon} aria-hidden="true" />
                    ) : canPurchase ? (
                        <ShoppingCartIcon size={styles.icon} strokeWidth={1.8} aria-hidden="true" />
                    ) : null}
                    <span className="truncate">{label}</span>
                </Button>
                <Button
                    size={styles.button}
                    variant="outline"
                    disabled={!canPurchase}
                    onClick={onBuyNow}
                    className="flex-1"
                >
                    {cart.buyingNow && <Spinner className={styles.spinner} />}
                    <span className="truncate">{__('Buy it now')}</span>
                </Button>
            </div>

            {cart.status === 'error' && cart.error && (
                <p role="alert" className="mt-3 flex items-center gap-1.5 text-sm font-medium text-error">
                    <CircleAlertIcon size={15} aria-hidden="true" />
                    {cart.error}
                </p>
            )}
        </>
    );
}
