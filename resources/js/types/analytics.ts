export interface AnalyticsItem {
    item_id: number;
    item_name: string;
    item_category?: string;
    item_brand?: string;
    item_variant?: string;
    price: number;
    quantity: number;
}

export interface ViewItemPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
}

export interface AddToCartPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
}

export interface BeginCheckoutPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
    coupon?: string;
}

export interface PurchasePayload {
    transaction_id: number;
    currency: string;
    value: number;
    tax: number;
    shipping: number;
    coupon?: string;
    items: AnalyticsItem[];
}

export interface ViewItemListPayload {
    item_list_id?: string;
    item_list_name?: string;
    items: AnalyticsItem[];
    currency: string;
}

export interface SelectItemPayload {
    item_list_id?: string;
    item_list_name?: string;
    items: AnalyticsItem[];
    currency: string;
}

export interface SearchPayload {
    search_term: string;
}

export interface RemoveFromCartPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
}

export interface ViewCartPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
}

export interface AddShippingInfoPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
    shipping_tier?: string;
    coupon?: string;
}

export interface AddPaymentInfoPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
    payment_type?: string;
    coupon?: string;
}

export interface AddToWishlistPayload {
    items: AnalyticsItem[];
    currency: string;
    value: number;
}

export interface SharePayload {
    method?: string;
    content_type: string;
    item_id: number;
}

export interface AnalyticsProvider {
    viewItem(payload: ViewItemPayload): void;
    addToCart(payload: AddToCartPayload): void;
    beginCheckout(payload: BeginCheckoutPayload): void;
    purchase(payload: PurchasePayload): void;
    viewItemList(payload: ViewItemListPayload): void;
    selectItem(payload: SelectItemPayload): void;
    search(payload: SearchPayload): void;
    removeFromCart(payload: RemoveFromCartPayload): void;
    viewCart(payload: ViewCartPayload): void;
    addShippingInfo(payload: AddShippingInfoPayload): void;
    addPaymentInfo(payload: AddPaymentInfoPayload): void;
    addToWishlist(payload: AddToWishlistPayload): void;
    share(payload: SharePayload): void;
}
