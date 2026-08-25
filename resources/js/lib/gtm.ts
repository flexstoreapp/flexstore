import type { AnalyticsProvider } from '@/types/analytics';

function pushToDataLayer(event: string, data: Record<string, unknown>): void {
    const w = window as unknown as Record<string, unknown>;
    const dataLayer = ((w.dataLayer as unknown[]) ?? []) as Record<string, unknown>[];
    dataLayer.push({ ecommerce: null });
    dataLayer.push({ event, ecommerce: data });
}

export const gtmProvider: AnalyticsProvider = {
    viewItem({ items, currency, value }) {
        pushToDataLayer('view_item', { currency, value, items });
    },
    addToCart({ items, currency, value }) {
        pushToDataLayer('add_to_cart', { currency, value, items });
    },
    beginCheckout({ items, currency, value, coupon }) {
        pushToDataLayer('begin_checkout', { currency, value, coupon, items });
    },
    purchase({ transaction_id, currency, value, tax, shipping, coupon, items }) {
        pushToDataLayer('purchase', { transaction_id, currency, value, tax, shipping, coupon, items });
    },
    viewItemList({ item_list_id, item_list_name, items, currency }) {
        pushToDataLayer('view_item_list', { item_list_id, item_list_name, currency, items });
    },
    selectItem({ item_list_id, item_list_name, items, currency }) {
        pushToDataLayer('select_item', { item_list_id, item_list_name, currency, items });
    },
    search({ search_term }) {
        pushToDataLayer('search', { search_term });
    },
    removeFromCart({ items, currency, value }) {
        pushToDataLayer('remove_from_cart', { currency, value, items });
    },
    viewCart({ items, currency, value }) {
        pushToDataLayer('view_cart', { currency, value, items });
    },
    addShippingInfo({ items, currency, value, shipping_tier, coupon }) {
        pushToDataLayer('add_shipping_info', { currency, value, shipping_tier, coupon, items });
    },
    addPaymentInfo({ items, currency, value, payment_type, coupon }) {
        pushToDataLayer('add_payment_info', { currency, value, payment_type, coupon, items });
    },
    addToWishlist({ items, currency, value }) {
        pushToDataLayer('add_to_wishlist', { currency, value, items });
    },
    share({ method, content_type, item_id }) {
        pushToDataLayer('share', { method, content_type, item_id });
    },
};
