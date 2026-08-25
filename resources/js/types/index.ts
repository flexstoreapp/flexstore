import type { Auth, User } from './auth';
import type { Cart } from './cart';
import type { Currency } from './currency';
import type { MediaItem } from './media';
import type { StorefrontLayout } from './storefront';
import type { AdminThemeColor, Appearance } from './theme';

export * from './theme';
export * from './translations';
export * from './address';
export * from './auth';
export * from './navigation';
export * from './dashboard';
export * from './order';
export * from './account';
export * from './checkout';
export * from './checkout-session';
export * from './product';
export * from './inventory';
export * from './category';
export * from './brand';
export * from './coupon';
export * from './customer';
export * from './setting';
export * from './region';
export * from './shipping';
export * from './tax';
export * from './currency';
export * from './payment';
export * from './gateway';
export * from './storefront';
export * from './cart';
export * from './analytics';
export * from './menu';
export * from './product-list';

export type WeightUnit = 'kg' | 'g' | 'lb' | 'oz';

export type TranslatableField = Record<string, string>;

export type Direction = 'ltr' | 'rtl';

export interface AvailableLocale {
    code: string;
    name: string;
}

export interface BaseSharedData {
    appUrl: string;
    storeName: string;
    storeLogo: MediaItem | null;
    storeLogoDark: MediaItem | null;
    activeLocale: string;
    defaultLocale: string;
    availableLocales: AvailableLocale[];
    sellingCountries: string[];
    direction: Direction;
    activeCurrency: string;
    baseCurrency: string;
    availableCurrencies: Pick<
        Currency,
        | 'code'
        | 'symbol'
        | 'symbol_position'
        | 'thousands_separator'
        | 'decimal_separator'
        | 'decimal_places'
        | 'exchange_rate'
    >[];
    appearance: Appearance;
    adminThemeColor: AdminThemeColor;
}

export interface AdminSharedData extends BaseSharedData {
    auth: Auth;
    sidebarOpen: boolean;
    timezone: string;
    update: { version: string; portal_url: string } | null;
    [key: string]: unknown;
}

export interface StorefrontAuth {
    user: User | null;
}

export interface ShareableList {
    id: string;
    product_ids: number[];
}

export interface StorefrontSharedData extends BaseSharedData {
    flash: { message?: string; error?: string };
    storefront: StorefrontLayout;
    cart: Cart;
    wishlist: ShareableList;
    auth: StorefrontAuth;
    [key: string]: unknown;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: Array<Record<string, string>>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface SimplePaginated<T> {
    data: T[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
}
