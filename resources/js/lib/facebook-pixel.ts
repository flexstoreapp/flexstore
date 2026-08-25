import type { AnalyticsProvider } from '@/types/analytics';

function fbq(command: string, event: string, data: Record<string, unknown>): void {
    const w = window as unknown as Record<string, unknown>;
    if (typeof w.fbq === 'function') {
        (w.fbq as (...args: unknown[]) => void)(command, event, data);
    }
}

export const facebookPixelProvider: AnalyticsProvider = {
    viewItem({ items, currency, value }) {
        const item = items[0];
        fbq('track', 'ViewContent', {
            content_ids: [item?.item_id],
            content_name: item?.item_name,
            content_category: item?.item_category,
            content_type: 'product',
            currency,
            value,
        });
    },
    addToCart({ items, currency, value }) {
        const item = items[0];
        fbq('track', 'AddToCart', {
            content_ids: [item?.item_id],
            content_name: item?.item_name,
            content_type: 'product',
            currency,
            value,
        });
    },
    beginCheckout({ items, currency, value }) {
        fbq('track', 'InitiateCheckout', {
            content_ids: items.map((i) => i.item_id),
            content_type: 'product',
            currency,
            value,
            num_items: items.reduce((sum, i) => sum + i.quantity, 0),
        });
    },
    purchase({ transaction_id, currency, value, items }) {
        fbq('track', 'Purchase', {
            content_ids: items.map((i) => i.item_id),
            content_type: 'product',
            currency,
            value,
            num_items: items.reduce((sum, i) => sum + i.quantity, 0),
            order_id: transaction_id,
        });
    },
    viewItemList({ items, currency }) {
        fbq('trackCustom', 'ViewItemList', {
            content_ids: items.map((i) => i.item_id),
            content_type: 'product',
            currency,
        });
    },
    selectItem({ items, currency }) {
        const item = items[0];
        fbq('trackCustom', 'SelectItem', {
            content_ids: [item?.item_id],
            content_name: item?.item_name,
            content_type: 'product',
            currency,
        });
    },
    search({ search_term }) {
        fbq('track', 'Search', {
            search_string: search_term,
        });
    },
    removeFromCart({ items, currency, value }) {
        fbq('trackCustom', 'RemoveFromCart', {
            content_ids: items.map((i) => i.item_id),
            content_type: 'product',
            currency,
            value,
        });
    },
    viewCart({ items, currency, value }) {
        fbq('trackCustom', 'ViewCart', {
            content_ids: items.map((i) => i.item_id),
            content_type: 'product',
            currency,
            value,
            num_items: items.reduce((sum, i) => sum + i.quantity, 0),
        });
    },
    addShippingInfo({ currency, value }) {
        fbq('trackCustom', 'AddShippingInfo', {
            currency,
            value,
        });
    },
    addPaymentInfo({ currency, value, payment_type }) {
        fbq('trackCustom', 'AddPaymentInfo', {
            currency,
            value,
            payment_type,
        });
    },
    addToWishlist({ items, currency, value }) {
        const item = items[0];
        fbq('track', 'AddToWishlist', {
            content_ids: [item?.item_id],
            content_name: item?.item_name,
            content_type: 'product',
            currency,
            value,
        });
    },
    share({ content_type, item_id }) {
        fbq('trackCustom', 'Share', {
            content_type,
            content_ids: [item_id],
        });
    },
};
