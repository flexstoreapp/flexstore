import { type UrlMethodPair } from '@inertiajs/core';
import { type LucideIcon } from 'lucide-react';

import { type Permission } from '@/lib/permissions';

import type { MediaItem } from './media';

export type SymbolPosition = 'before' | 'before_with_space' | 'after' | 'after_with_space';
export type DisplayTaxTotals = 'itemized' | 'single';

export interface SettingGroup {
    title: string;
    description: string;
    icon: LucideIcon;
    href: UrlMethodPair;
    permission?: Permission;
}

export interface GeneralSettings {
    default_low_stock_threshold: number;
    auto_approve_reviews: boolean;
}

export interface StoreSettings {
    store_name: string;
    store_description: string;
    store_email: string;
    store_phone: string;
    store_street_address: string;
    store_city: string;
    store_state: string;
    store_postal_code: string;
    store_country_code: string;
    selling_countries: string[];
    store_logo: MediaItem | null;
    store_logo_dark: MediaItem | null;
    store_favicon: MediaItem | null;
    store_social_facebook: string | null;
    store_social_instagram: string | null;
    store_social_x: string | null;
    store_social_tiktok: string | null;
    store_social_pinterest: string | null;
    store_social_youtube: string | null;
}

export interface TaxSettings {
    prices_include_tax: boolean;
    tax_based_on: string;
    default_tax_rate: string;
    shipping_is_taxable: boolean;
    display_tax_totals: DisplayTaxTotals;
}

export interface CheckoutSettings {
    guest_checkout_enabled: boolean;
    checkout_reservation_minutes: number;
}

export interface CurrencySettings {
    base_currency: string;
}

export interface LanguageSettings {
    default_locale: string;
    available_locales: string[];
}

export interface PolicySettings {
    refund_policy: string;
    privacy_policy: string;
    terms_of_service: string;
}

export interface SeoSettings {
    seo_homepage_meta_title: string | null;
    seo_homepage_meta_description: string | null;
    seo_shop_meta_title: string | null;
    seo_shop_meta_description: string | null;
    seo_robots_indexing: boolean;
}

export interface IntegrationSettings {
    integration_google_analytics_id: string | null;
    integration_google_tag_manager_id: string | null;
    integration_meta_pixel_id: string | null;
    integration_tiktok_pixel_id: string | null;
    integration_pinterest_tag_id: string | null;
    integration_google_login_client_id: string | null;
    integration_google_login_client_secret: string | null;
    integration_google_merchant_enabled: boolean;
    integration_google_merchant_token: string | null;
    integration_google_merchant_include_digital: boolean;
    integration_meta_catalog_enabled: boolean;
    integration_meta_catalog_token: string | null;
}

export type MailEncryption = 'tls' | 'ssl';

export interface MailSettings {
    mail_host: string | null;
    mail_port: number | null;
    mail_encryption: MailEncryption | null;
    mail_username: string | null;
    mail_password: string | null;
    mail_from_address: string | null;
    mail_from_name: string | null;
}

export interface NotificationSettings {
    notification_admin_new_order: boolean;
    notification_admin_order_canceled: boolean;
    notification_admin_low_stock: boolean;
    notification_admin_new_customer: boolean;
    notification_admin_new_review: boolean;
    notification_customer_order_confirmed: boolean;
    notification_customer_abandoned_checkout: boolean;
}
