import type { AnalyticsProvider } from '@/types/analytics';

function gtag(...args: unknown[]): void {
    const w = window as unknown as Record<string, unknown>;
    const dataLayer = (w.dataLayer ?? []) as unknown[];
    dataLayer.push(args);
}

export const ga4Provider: AnalyticsProvider = {
    viewItem({ items, currency, value }) {
        gtag('event', 'view_item', { currency, value, items });
    },
    addToCart({ items, currency, value }) {
        gtag('event', 'add_to_cart', { currency, value, items });
    },
    beginCheckout({ items, currency, value, coupon }) {
        gtag('event', 'begin_checkout', { currency, value, coupon, items });
    },
    purchase({ transaction_id, currency, value, tax, shipping, coupon, items }) {
        gtag('event', 'purchase', { transaction_id, currency, value, tax, shipping, coupon, items });
    },
    viewItemList({ item_list_id, item_list_name, items, currency }) {
        gtag('event', 'view_item_list', { item_list_id, item_list_name, currency, items });
    },
    selectItem({ item_list_id, item_list_name, items, currency }) {
        gtag('event', 'select_item', { item_list_id, item_list_name, currency, items });
    },
    search({ search_term }) {
        gtag('event', 'search', { search_term });
    },
    removeFromCart({ items, currency, value }) {
        gtag('event', 'remove_from_cart', { currency, value, items });
    },
    viewCart({ items, currency, value }) {
        gtag('event', 'view_cart', { currency, value, items });
    },
    addShippingInfo({ items, currency, value, shipping_tier, coupon }) {
        gtag('event', 'add_shipping_info', { currency, value, shipping_tier, coupon, items });
    },
    addPaymentInfo({ items, currency, value, payment_type, coupon }) {
        gtag('event', 'add_payment_info', { currency, value, payment_type, coupon, items });
    },
    addToWishlist({ items, currency, value }) {
        gtag('event', 'add_to_wishlist', { currency, value, items });
    },
    share({ method, content_type, item_id }) {
        gtag('event', 'share', { method, content_type, item_id });
    },
};
