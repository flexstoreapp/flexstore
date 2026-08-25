import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import * as CartItemController from '@/actions/App/Http/Controllers/Storefront/CartItemController';
import { useAbortableRequest } from '@/hooks/storefront/use-abortable-request';
import { analytics } from '@/lib/analytics';
import type { CartItem, StorefrontSharedData } from '@/types';

export function useCartItem(item: CartItem) {
    const { activeCurrency } = usePage<StorefrontSharedData>().props;
    const { run } = useAbortableRequest();
    const [override, setOverride] = useState<number | null>(null);
    const [updating, setUpdating] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [error, setError] = useState<string>();

    const quantity = override ?? item.quantity;

    const setQuantity = (next: number) => {
        const delta = next - quantity;
        setOverride(next);
        setError(undefined);
        setUpdating(true);
        run((capture) =>
            router.patch(
                CartItemController.update.url({ cartItem: item.id }),
                { quantity: next },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onCancelToken: capture,
                    onSuccess: () => {
                        setOverride(null);
                        analytics.changeCartQuantity(item, delta, activeCurrency);
                    },
                    onError: (errors: Record<string, string>) => {
                        setOverride(null);
                        setError(Object.values(errors)[0]);
                    },
                    onFinish: () => setUpdating(false),
                },
            ),
        );
    };

    const remove = () => {
        setRemoving(true);
        run((capture) =>
            router.delete(CartItemController.destroy.url({ cartItem: item.id }), {
                preserveScroll: true,
                preserveState: true,
                onCancelToken: capture,
                onSuccess: () => analytics.removeFromCart(item, activeCurrency),
                onFinish: () => setRemoving(false),
            }),
        );
    };

    return { quantity, updating, removing, error, setQuantity, remove };
}
