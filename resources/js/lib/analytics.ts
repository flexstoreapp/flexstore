import type { Brand, Cart, CartItem, Category, OrderItem, Product, ProductData, ProductVariant } from '@/types';
import type { AnalyticsProvider } from '@/types/analytics';

import { facebookPixelProvider } from './facebook-pixel';
import { ga4Provider } from './ga4';
import { gtmProvider } from './gtm';
import { pinterestTagProvider } from './pinterest-tag';
import { tiktokPixelProvider } from './tiktok-pixel';
import { getTranslation } from './utils';

export interface PurchaseOrderItem {
    product_id: number | null;
    product_title: OrderItem['product_title'];
    variant_title: string | null;
    unit_price: string;
    quantity: number;
}

export interface PurchaseOrder {
    id: number;
    currency_code: string;
    total: string;
    tax_total: string;
    shipping_total: string;
    coupon_code: string | null;
    items: PurchaseOrderItem[];
}

function getActiveProviders(): AnalyticsProvider[] {
    const w = window as unknown as Record<string, unknown>;
    const providers: AnalyticsProvider[] = [];

    const dataLayer = w.dataLayer as unknown[] | undefined;
    const hasGtm =
        Array.isArray(dataLayer) && dataLayer.some((e) => typeof e === 'object' && e !== null && 'gtm.start' in e);

    if (hasGtm) {
        providers.push(gtmProvider);
    } else if (typeof w.gtag === 'function') {
        providers.push(ga4Provider);
    }

    if (typeof w.fbq === 'function') {
        providers.push(facebookPixelProvider);
    }

    if (typeof w.ttq === 'object' && w.ttq !== null) {
        providers.push(tiktokPixelProvider);
    }

    if (typeof w.pintrk === 'function') {
        providers.push(pinterestTagProvider);
    }

    return providers;
}

function dispatch<K extends keyof AnalyticsProvider>(event: K, payload: Parameters<AnalyticsProvider[K]>[0]): void {
    for (const provider of getActiveProviders()) {
        try {
            (provider[event] as (p: typeof payload) => void)(payload);
        } catch {
            // silently ignore provider errors
        }
    }
}

function toAnalyticsItem(
    product: Pick<Product, 'id' | 'title'> & {
        category?: Pick<Category, 'name'> | null;
        brand?: Pick<Brand, 'name'> | null;
    },
    price: string | null,
    quantity: number,
    variant?: Pick<ProductVariant, 'title'> | null,
) {
    return {
        item_id: product.id,
        item_name: getTranslation(product.title, 'Product'),
        item_category: product.category ? getTranslation(product.category.name) : undefined,
        item_brand: product.brand ? getTranslation(product.brand.name) : undefined,
        item_variant: variant ? getTranslation(variant.title) : undefined,
        price: parseFloat(price ?? '0'),
        quantity,
    };
}

function cartItemToAnalyticsItem(item: CartItem) {
    return {
        item_id: item.product_id,
        item_name: item.product ? getTranslation(item.product.title, 'Product') : String(item.product_id),
        item_category: item.product?.category ? getTranslation(item.product.category.name) : undefined,
        item_brand: item.product?.brand ? getTranslation(item.product.brand.name) : undefined,
        item_variant: item.variant_title ?? undefined,
        price: parseFloat(item.unit_price),
        quantity: item.quantity,
    };
}

function orderItemToAnalyticsItem(item: PurchaseOrderItem) {
    return {
        item_id: item.product_id ?? 0,
        item_name: getTranslation(item.product_title, 'Product'),
        item_variant: item.variant_title ?? undefined,
        price: parseFloat(item.unit_price),
        quantity: item.quantity,
    };
}

export const analytics = {
    viewItem(
        product: Pick<Product, 'id' | 'title'> & {
            category?: Pick<Category, 'name'> | null;
            brand?: Pick<Brand, 'name'> | null;
        },
        price: string | null,
        currency: string,
        variant?: Pick<ProductVariant, 'title'> | null,
    ) {
        const item = toAnalyticsItem(product, price, 1, variant);

        dispatch('viewItem', {
            items: [item],
            currency,
            value: item.price,
        });
    },

    addToCart(
        product: Pick<Product, 'id' | 'title'> & {
            category?: Pick<Category, 'name'> | null;
            brand?: Pick<Brand, 'name'> | null;
        },
        price: string | null,
        quantity: number,
        currency: string,
        variant?: Pick<ProductVariant, 'title'> | null,
    ) {
        const item = toAnalyticsItem(product, price, quantity, variant);

        dispatch('addToCart', {
            items: [item],
            currency,
            value: item.price * quantity,
        });
    },

    beginCheckout(cart: Cart, currency: string) {
        const items = (cart.items ?? []).map(cartItemToAnalyticsItem);

        dispatch('beginCheckout', {
            items,
            currency,
            value: parseFloat(cart.total),
            coupon: cart.coupon_code ?? undefined,
        });
    },

    purchase(order: PurchaseOrder) {
        const items = (order.items ?? []).map(orderItemToAnalyticsItem);

        dispatch('purchase', {
            transaction_id: order.id,
            currency: order.currency_code,
            value: parseFloat(order.total),
            tax: parseFloat(order.tax_total),
            shipping: parseFloat(order.shipping_total),
            coupon: order.coupon_code ?? undefined,
            items,
        });
    },

    viewItemList(products: ProductData[], currency: string, listId?: string, listName?: string) {
        const items = products.map((p) => ({
            item_id: p.id,
            item_name: getTranslation(p.title, 'Product'),
            price: parseFloat(p.price ?? '0'),
            quantity: 1,
        }));

        dispatch('viewItemList', {
            item_list_id: listId,
            item_list_name: listName,
            items,
            currency,
        });
    },

    selectItem(product: ProductData, currency: string, listId?: string, listName?: string) {
        dispatch('selectItem', {
            item_list_id: listId,
            item_list_name: listName,
            items: [
                {
                    item_id: product.id,
                    item_name: getTranslation(product.title, 'Product'),
                    price: parseFloat(product.price ?? '0'),
                    quantity: 1,
                },
            ],
            currency,
        });
    },

    search(searchTerm: string) {
        dispatch('search', { search_term: searchTerm });
    },

    removeFromCart(item: CartItem, currency: string) {
        const analyticsItem = cartItemToAnalyticsItem(item);

        dispatch('removeFromCart', {
            items: [analyticsItem],
            currency,
            value: analyticsItem.price * analyticsItem.quantity,
        });
    },

    changeCartQuantity(item: CartItem, delta: number, currency: string) {
        if (delta === 0) {
            return;
        }

        const quantity = Math.abs(delta);
        const analyticsItem = { ...cartItemToAnalyticsItem(item), quantity };
        const payload = { items: [analyticsItem], currency, value: analyticsItem.price * quantity };

        if (delta > 0) {
            dispatch('addToCart', payload);
        } else {
            dispatch('removeFromCart', payload);
        }
    },

    viewCart(cart: Cart, currency: string) {
        const items = (cart.items ?? []).map(cartItemToAnalyticsItem);

        dispatch('viewCart', {
            items,
            currency,
            value: parseFloat(cart.total),
        });
    },

    addShippingInfo(cart: Cart, currency: string, shippingTier?: string) {
        const items = (cart.items ?? []).map(cartItemToAnalyticsItem);

        dispatch('addShippingInfo', {
            items,
            currency,
            value: parseFloat(cart.total),
            shipping_tier: shippingTier,
            coupon: cart.coupon_code ?? undefined,
        });
    },

    addPaymentInfo(cart: Cart, currency: string, paymentType?: string) {
        const items = (cart.items ?? []).map(cartItemToAnalyticsItem);

        dispatch('addPaymentInfo', {
            items,
            currency,
            value: parseFloat(cart.total),
            payment_type: paymentType,
            coupon: cart.coupon_code ?? undefined,
        });
    },

    addToWishlist(
        product: Pick<Product, 'id' | 'title'> & {
            category?: Pick<Category, 'name'> | null;
            brand?: Pick<Brand, 'name'> | null;
        },
        price: string | null,
        currency: string,
    ) {
        const item = toAnalyticsItem(product, price, 1);

        dispatch('addToWishlist', {
            items: [item],
            currency,
            value: item.price,
        });
    },

    share(productId: number, method?: string) {
        dispatch('share', {
            method,
            content_type: 'product',
            item_id: productId,
        });
    },
};
