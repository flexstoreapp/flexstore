import type { AnalyticsProvider } from '@/types/analytics';

function ttq(method: string, event: string, data?: Record<string, unknown>): void {
    const w = window as unknown as Record<string, unknown>;
    if (typeof w.ttq !== 'object' || w.ttq === null) return;
    const instance = w.ttq as Record<string, (...args: unknown[]) => void>;
    if (typeof instance[method] === 'function') {
        instance[method](event, data);
    }
}

export const tiktokPixelProvider: AnalyticsProvider = {
    viewItem({ items, currency, value }) {
        const item = items[0];
        ttq('track', 'ViewContent', {
            content_id: String(item?.item_id),
            content_name: item?.item_name,
            content_category: item?.item_category,
            content_type: 'product',
            currency,
            value,
            quantity: item?.quantity ?? 1,
        });
    },
    addToCart({ items, currency, value }) {
        const item = items[0];
        ttq('track', 'AddToCart', {
            content_id: String(item?.item_id),
            content_name: item?.item_name,
            content_type: 'product',
            currency,
            value,
            quantity: item?.quantity ?? 1,
        });
    },
    beginCheckout({ items, currency, value }) {
        ttq('track', 'InitiateCheckout', {
            content_type: 'product',
            contents: items.map((i) => ({
                content_id: String(i.item_id),
                content_name: i.item_name,
                quantity: i.quantity,
                price: i.price,
            })),
            currency,
            value,
        });
    },
    purchase({ currency, value, items }) {
        ttq('track', 'CompletePayment', {
            content_type: 'product',
            contents: items.map((i) => ({
                content_id: String(i.item_id),
                content_name: i.item_name,
                quantity: i.quantity,
                price: i.price,
            })),
            currency,
            value,
        });
    },
    viewItemList({ items, currency }) {
        ttq('track', 'ViewContent', {
            content_type: 'product_group',
            contents: items.map((i) => ({
                content_id: String(i.item_id),
                content_name: i.item_name,
            })),
            currency,
        });
    },
    selectItem({ items, currency }) {
        const item = items[0];
        ttq('track', 'ClickButton', {
            content_id: String(item?.item_id),
            content_name: item?.item_name,
            content_type: 'product',
            currency,
        });
    },
    search({ search_term }) {
        ttq('track', 'Search', {
            query: search_term,
        });
    },
    removeFromCart({ items, currency, value }) {
        const item = items[0];
        ttq('track', 'RemoveFromCart', {
            content_id: String(item?.item_id),
            content_type: 'product',
            currency,
            value,
        });
    },
    viewCart({ items, currency, value }) {
        ttq('track', 'ViewContent', {
            content_type: 'product',
            contents: items.map((i) => ({
                content_id: String(i.item_id),
                quantity: i.quantity,
                price: i.price,
            })),
            currency,
            value,
        });
    },
    addShippingInfo({ currency, value }) {
        ttq('track', 'AddShippingInfo', {
            currency,
            value,
        });
    },
    addPaymentInfo({ currency, value, payment_type }) {
        ttq('track', 'AddPaymentInfo', {
            currency,
            value,
            payment_type,
        });
    },
    addToWishlist({ items, currency, value }) {
        const item = items[0];
        ttq('track', 'AddToWishlist', {
            content_id: String(item?.item_id),
            content_name: item?.item_name,
            content_type: 'product',
            currency,
            value,
        });
    },
    share({ content_type, item_id }) {
        ttq('track', 'Share', {
            content_type,
            content_id: String(item_id),
        });
    },
};
