import type { Product, ProductVariant, User } from '.';

export interface CartItem {
    id: number;
    cart_id: string;
    product_id: number;
    product_variant_id?: string | null;
    quantity: number;
    unit_price: string;
    compare_at_price?: string | null;
    total_price: string;
    variant_title?: string | null;
    variant_options?: Record<string, string> | null;
    product?: Product;
    product_variant?: Pick<ProductVariant, 'id' | 'product_id' | 'media'>;
    created_at: string;
    updated_at: string;
}

export interface Cart {
    id: string;
    customer_id?: number | null;
    customer?: User | null;
    shipping_rate_id?: number | null;
    shipping_quote_reference?: string | null;
    payment_gateway_id?: number | null;
    coupon_code?: string | null;
    subtotal: string;
    tax_total: string;
    shipping_total: string;
    discount_total: string;
    total: string;
    requires_shipping: boolean;
    items?: CartItem[];
    created_at: string;
    updated_at: string;
}
