import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import * as WishlistItemController from '@/actions/App/Http/Controllers/Storefront/WishlistItemController';
import { useAbortableRequest } from '@/hooks/storefront/use-abortable-request';
import type { StorefrontSharedData } from '@/types';

interface ProductListToggle {
    active: boolean;
    toggle: () => void;
}

interface Endpoints {
    add: string;
    remove: string;
    only: string;
}

function useToggleMembership(
    productId: number,
    memberIds: number[],
    endpoints: Endpoints,
    onActivate?: () => void,
): ProductListToggle {
    const { run } = useAbortableRequest();
    const serverActive = memberIds.includes(productId);
    const [desired, setDesired] = useState<boolean | null>(null);
    const active = desired ?? serverActive;

    const toggle = () => {
        const next = !active;
        setDesired(next);
        run((capture) => {
            const options = {
                preserveScroll: true,
                preserveState: true,
                only: [endpoints.only],
                onCancelToken: capture,
                onSuccess: () => {
                    setDesired(null);
                    if (next) {
                        onActivate?.();
                    }
                },
                onError: () => setDesired(null),
            };

            if (next) {
                router.put(endpoints.add, {}, options);
            } else {
                router.delete(endpoints.remove, options);
            }
        });
    };

    return { active, toggle };
}

export function useWishlist(productId: number, onAdd?: () => void): ProductListToggle {
    const { wishlist } = usePage<StorefrontSharedData>().props;
    return useToggleMembership(
        productId,
        wishlist.product_ids,
        {
            add: WishlistItemController.update.url({ product: productId }),
            remove: WishlistItemController.destroy.url({ product: productId }),
            only: 'wishlist',
        },
        onAdd,
    );
}
