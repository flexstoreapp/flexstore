import { type SelectableBrand } from '@/components/admin/brand-picker';
import { type SelectableItem as CategorySelectableItem } from '@/components/admin/category-picker';
import { type SelectableItem as CurrencySelectableItem } from '@/components/admin/currency-picker';
import { type SelectableItem as ProductSelectableItem } from '@/components/admin/product-picker';
import { type SelectableItem as RegionSelectableItem } from '@/components/admin/region-picker';

import type { TranslatableField } from './';

export type PaymentGatewayDriver =
    'stripe' | 'paypal' | 'razorpay' | 'mollie' | 'tap' | 'paystack' | 'mercadopago' | 'cod';

export type CheckoutMode = 'hosted' | 'embedded';

export interface StripeCredentials {
    publishable_key: string;
    secret_key: string;
    signing_secret: string;
    checkout_mode: CheckoutMode;
}

export interface PaypalCredentials {
    client_id: string;
    client_secret: string;
    webhook_id: string;
    checkout_mode: CheckoutMode;
    sandbox: boolean;
}

export interface RazorpayCredentials {
    key_id: string;
    key_secret: string;
    webhook_secret: string;
    checkout_mode: CheckoutMode;
}

export interface MollieCredentials {
    api_key: string;
    profile_id: string;
    checkout_mode: CheckoutMode;
}

export interface TapCredentials {
    public_key: string;
    secret_key: string;
    checkout_mode: CheckoutMode;
}

export interface PaystackCredentials {
    public_key: string;
    secret_key: string;
    checkout_mode: CheckoutMode;
}

export interface MercadoPagoCredentials {
    public_key: string;
    access_token: string;
    webhook_secret: string;
    checkout_mode: CheckoutMode;
}

export type PaymentRequestStatus = 'open' | 'completed' | 'canceled' | 'expired';

export interface PaymentOption {
    id: number;
    name: TranslatableField;
    driver: PaymentGatewayDriver;
    checkout_mode?: CheckoutMode;
    publishable_key?: string;
    client_id?: string;
    key_id?: string;
    public_key?: string;
    profile_id?: string;
    testmode?: boolean;
}

export interface PaymentGateway {
    id: number;
    name: TranslatableField;
    driver: PaymentGatewayDriver;
    credentials:
        | StripeCredentials
        | PaypalCredentials
        | RazorpayCredentials
        | MollieCredentials
        | TapCredentials
        | PaystackCredentials
        | MercadoPagoCredentials
        | null;
    min_order_value: string | null;
    max_order_value: string | null;
    min_weight: string | null;
    min_weight_unit: string | null;
    max_weight: string | null;
    max_weight_unit: string | null;
    excluded_products: string[] | ProductSelectableItem[];
    excluded_categories: string[] | CategorySelectableItem[];
    excluded_brands: string[] | SelectableBrand[];
    allowed_regions: string[] | RegionSelectableItem[];
    supported_currencies: CurrencySelectableItem[];
    sync_external_refunds: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}
