import type { CheckoutSessionAddress } from './checkout-session';

export interface AddressFormValues {
    first_name: string;
    last_name: string;
    address_line_1: string;
    address_line_2: string;
    city: string;
    state: string;
    postal_code: string;
    country_code: string;
    phone: string;
}

export interface CheckoutFormValues {
    customer_email: string;
    shipping_address: AddressFormValues;
    different_billing_address: boolean;
    billing_address: AddressFormValues;
    save_shipping_address: boolean;
    notes: string;
    coupon_code: string;
    shipping_rate_id: string;
    shipping_quote_reference: string;
    payment_gateway_id: string;
}

export type AddressPrefix = 'shipping_address' | 'billing_address';

export type RecoveredAddress = CheckoutSessionAddress;

export interface RecoveredCheckout {
    customer_email: string | null;
    shipping_address: RecoveredAddress | null;
    billing_address: RecoveredAddress | null;
    different_billing_address: boolean;
    notes: string | null;
    customer_address_id: number | null;
}
