import type { AnalyticsProvider } from '@/types/analytics';

function pintrk(command: string, event: string, data?: Record<string, unknown>): void {
    const w = window as unknown as Record<string, unknown>;
    if (typeof w.pintrk === 'function') {
        (w.pintrk as (...args: unknown[]) => void)(command, event, data);
    }
}

export const pinterestTagProvider: AnalyticsProvider = {
    viewItem({ items, currency, value }) {
        const item = items[0];
        pintrk('track', 'pagevisit', {
            product_id: item?.item_id,
            product_name: item?.item_name,
            product_category: item?.item_category,
            currency,
            value,
            line_items: items.map((i) => ({
                product_id: i.item_id,
                product_name: i.item_name,
                product_price: i.price,
                product_quantity: i.quantity,
            })),
        });
    },
    addToCart({ items, currency, value }) {
        pintrk('track', 'addtocart', {
            currency,
            value,
            order_quantity: items.reduce((sum, i) => sum + i.quantity, 0),
            line_items: items.map((i) => ({
                product_id: i.item_id,
                product_name: i.item_name,
                product_price: i.price,
                product_quantity: i.quantity,
            })),
        });
    },
    beginCheckout({ items, currency, value }) {
        pintrk('track', 'checkout', {
            currency,
            value,
            order_quantity: items.reduce((sum, i) => sum + i.quantity, 0),
            line_items: items.map((i) => ({
                product_id: i.item_id,
                product_name: i.item_name,
                product_price: i.price,
                product_quantity: i.quantity,
            })),
        });
    },
    purchase({ currency, value, items }) {
        pintrk('track', 'checkout', {
            currency,
            value,
            order_quantity: items.reduce((sum, i) => sum + i.quantity, 0),
            line_items: items.map((i) => ({
                product_id: i.item_id,
                product_name: i.item_name,
                product_price: i.price,
                product_quantity: i.quantity,
            })),
        });
    },
    viewItemList({ items, currency }) {
        pintrk('track', 'pagevisit', {
            currency,
            line_items: items.map((i) => ({
                product_id: i.item_id,
                product_name: i.item_name,
            })),
        });
    },
    selectItem({ items, currency }) {
        const item = items[0];
        pintrk('track', 'pagevisit', {
            product_id: item?.item_id,
            product_name: item?.item_name,
            currency,
        });
    },
    search({ search_term }) {
        pintrk('track', 'search', {
            search_query: search_term,
        });
    },
    removeFromCart({ items, currency, value }) {
        pintrk('track', 'custom', {
            event_name: 'remove_from_cart',
            currency,
            value,
            line_items: items.map((i) => ({
                product_id: i.item_id,
                product_quantity: i.quantity,
            })),
        });
    },
    viewCart({ items, currency, value }) {
        pintrk('track', 'custom', {
            event_name: 'view_cart',
            currency,
            value,
            order_quantity: items.reduce((sum, i) => sum + i.quantity, 0),
        });
    },
    addShippingInfo({ currency, value }) {
        pintrk('track', 'custom', {
            event_name: 'add_shipping_info',
            currency,
            value,
        });
    },
    addPaymentInfo({ currency, value, payment_type }) {
        pintrk('track', 'custom', {
            event_name: 'add_payment_info',
            currency,
            value,
            payment_type,
        });
    },
    addToWishlist({ items, currency, value }) {
        const item = items[0];
        pintrk('track', 'custom', {
            event_name: 'add_to_wishlist',
            product_id: item?.item_id,
            product_name: item?.item_name,
            currency,
            value,
        });
    },
    share({ content_type, item_id }) {
        pintrk('track', 'custom', {
            event_name: 'share',
            content_type,
            product_id: item_id,
        });
    },
};
