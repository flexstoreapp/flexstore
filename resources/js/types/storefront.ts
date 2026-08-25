import type { TranslatableField } from '.';
import type { User } from './auth';
import type { Brand } from './brand';
import type { Category } from './category';
import type { MediaItem } from './media';
import type { ProductType, Product, ProductOption, ProductOptionValue, ProductVariant, Review } from './product';

export type StorefrontSectionType =
    | 'hero_slider'
    | 'info_strip'
    | 'category_grid'
    | 'featured_products'
    | 'product_tabs'
    | 'promo_banners'
    | 'product_carousel'
    | 'product_columns'
    | 'brand_strip'
    | 'testimonials';

export type ProductSource = 'latest' | 'featured' | 'category' | 'brand';

export type PaymentMethodPreset = 'all' | 'credit_cards' | 'digital_wallets' | 'custom';
export type PaymentMethodName =
    | 'visa'
    | 'mastercard'
    | 'amex'
    | 'discover'
    | 'jcb'
    | 'unionpay'
    | 'mada'
    | 'paypal'
    | 'apple_pay'
    | 'google_pay'
    | 'upi'
    | 'ideal';

export type SocialPlatform = 'facebook' | 'instagram' | 'x' | 'tiktok' | 'pinterest' | 'youtube';

export interface FooterSettings {
    show_copyright: boolean;
    show_social_links: boolean;
    show_payment_badges: boolean;
    payment_method_preset: PaymentMethodPreset;
    payment_methods: PaymentMethodName[];
    copyright_text: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    app_store_url: string | null;
    play_store_url: string | null;
    social_links?: Partial<Record<SocialPlatform, string>>;
}

export interface HeaderSettings {
    sticky: boolean;
}

/** A product selected in an admin picker, resolved for re-populating the form. */
export interface SectionProductRef {
    id: number;
    type: ProductType;
    title: TranslatableField;
    price: string | null;
    price_range: [string, string] | null;
    featured_media: MediaItem | null;
}

/** A resolved category selection, resolved for re-populating the form. */
export type SectionCategoryRef = Pick<Category, 'id' | 'name'>;

/** Shared admin fields for any product-source-driven section or group (tab/column). */
export type SectionBrandRef = Pick<Brand, 'id' | 'name'>;

export interface ProductSourceSettings {
    product_source: ProductSource;
    product_limit: number;
    category_id: number | null;
    brand_id: number | null;
    product_ids: number[] | null;
    category?: SectionCategoryRef;
    brand?: SectionBrandRef;
    products?: SectionProductRef[];
}

/** Shared render fields for any product-source-driven section or group. */
export interface ProductSourceRenderSettings {
    product_source: ProductSource;
    product_limit: number;
    category_id: number | null;
    product_ids: number[] | null;
    products: ProductData[];
}

export interface ProductDetailSettings {
    show_info_strip: boolean;
    info_strip: InfoStripItem[];
    show_related_products: boolean;
    related_products_count: number;
    show_reviews: boolean;
    reviews_per_page: number;
}

// ---------------------------------------------------------------------------
// Admin builder settings (per section type) — translatable text as maps.
// ---------------------------------------------------------------------------

export interface HeroSlide {
    image: MediaItem | null;
    text_color?: SectionTextColor;
    text_align?: SectionTextAlign;
    headline: TranslatableField | null;
    subtext: TranslatableField | null;
    button_text: TranslatableField | null;
    button_url: string | null;
}

export interface HeroSideTile {
    image: MediaItem | null;
    text_color?: SectionTextColor;
    text_align?: SectionTextAlign;
    title: TranslatableField | null;
    subtitle: TranslatableField | null;
    url: string | null;
}

export type SectionTextColor = 'dark' | 'light';

export type SectionTextAlign =
    | 'top-left'
    | 'top-center'
    | 'top-right'
    | 'left'
    | 'center'
    | 'right'
    | 'bottom-left'
    | 'bottom-center'
    | 'bottom-right';

export type HeroTransition = 'fade' | 'slide';

export interface HeroSliderSettings {
    slides: HeroSlide[];
    side_tiles: HeroSideTile[];
    autoplay: boolean;
    autoplay_speed: number;
    transition: HeroTransition;
    show_dots: boolean;
}

export interface InfoStripItem {
    icon_name: string;
    title: TranslatableField;
    subtitle: TranslatableField | null;
}

export interface InfoStripSettings {
    items: InfoStripItem[];
}

export interface CategoryGridItem {
    category_id: number;
    text_color?: SectionTextColor;
    name: TranslatableField;
    url_handle?: string;
    image: MediaItem | null;
}

export interface CategoryGridSettings {
    categories: CategoryGridItem[];
}

export type FeaturedProductsSettings = ProductSourceSettings;

export interface ProductTab extends ProductSourceSettings {
    label: TranslatableField;
}

export interface ProductTabsSettings {
    tabs: ProductTab[];
}

export interface PromoBanner {
    image: MediaItem | null;
    text_color?: SectionTextColor;
    text_align?: SectionTextAlign;
    title: TranslatableField | null;
    subtitle: TranslatableField | null;
    url: string | null;
}

export interface PromoBannersSettings {
    banners: PromoBanner[];
}

export type ProductCarouselSettings = ProductSourceSettings;

export interface ProductColumn extends ProductSourceSettings {
    heading: TranslatableField;
}

export interface ProductColumnsSettings {
    columns: ProductColumn[];
}

export type BrandStripItem = Pick<Brand, 'id' | 'name' | 'url_handle' | 'image'>;

export interface BrandStripSettings {
    brand_ids: number[];
    grayscale: boolean;
    brands?: BrandStripItem[];
}

export interface TestimonialItem {
    quote: TranslatableField;
    author_name: TranslatableField;
    rating: number;
}

export interface TestimonialsSettings {
    testimonials: TestimonialItem[];
}

export type StorefrontSectionSettings =
    | HeroSliderSettings
    | InfoStripSettings
    | CategoryGridSettings
    | FeaturedProductsSettings
    | ProductTabsSettings
    | PromoBannersSettings
    | ProductCarouselSettings
    | ProductColumnsSettings
    | BrandStripSettings
    | TestimonialsSettings;

export interface StorefrontSection {
    id: number;
    type: StorefrontSectionType;
    title: TranslatableField;
    settings: StorefrontSectionSettings | null;
    sort_order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export type ProductData = Required<
    Pick<
        Product,
        | 'id'
        | 'url_handle'
        | 'title'
        | 'price'
        | 'price_range'
        | 'compare_at_price'
        | 'compare_at_price_range'
        | 'featured_media'
        | 'in_stock'
        | 'rating'
        | 'review_count'
        | 'has_variants'
    >
> & {
    brand?: Pick<Brand, 'name' | 'url_handle'> | null;
};

export type ProductBuyBoxOptionValue = Pick<ProductOptionValue, 'id' | 'value'>;

export interface ProductBuyBoxOption extends Pick<ProductOption, 'id' | 'name'> {
    values: ProductBuyBoxOptionValue[];
}

export interface ProductBuyBoxVariant extends Required<
    Pick<ProductVariant, 'id' | 'title' | 'sku' | 'barcode' | 'compare_at_price' | 'in_stock' | 'is_default' | 'media'>
> {
    price: string | null;
    max_quantity: number | null;
    option_values: Record<string, string>;
}

export interface ProductBuyBoxData {
    id: number;
    url_handle: string;
    title: TranslatableField;
    description: TranslatableField;
    sku: string | null;
    brand: Pick<Brand, 'name' | 'url_handle'> | null;
    category: Pick<Category, 'name' | 'url_handle'> | null;
    price: string | null;
    price_range: [string, string] | null;
    compare_at_price: string | null;
    compare_at_price_range: [string, string] | null;
    in_stock: boolean;
    max_quantity: number | null;
    rating: number | null;
    review_count: number;
    prices_include_tax: boolean;
    has_variants: boolean;
    media: MediaItem[];
    featured_media: MediaItem | null;
    options: ProductBuyBoxOption[];
    variants: ProductBuyBoxVariant[];
}

export type ProductCategoryRef = Pick<Category, 'name' | 'url_handle'>;

export interface ProductDetailData extends Omit<ProductBuyBoxData, 'category'> {
    type: ProductType;
    seo_title: TranslatableField;
    seo_description: TranslatableField;
    barcode: string | null;
    category: (ProductCategoryRef & { ancestors: ProductCategoryRef[] }) | null;
    rating_distribution: Record<number, number>;
}

export interface OrderLine {
    product_title: TranslatableField;
    variant_title: string | null;
    url_handle: string | null;
    featured_media: MediaItem | null;
    quantity: number;
    total_price: string;
}

export interface OrderShipmentGroup {
    key: string;
    carrier_name: string | null;
    tracking_number: string | null;
    tracking_url: string | null;
    shipped: boolean;
    refunded?: boolean;
    digital?: boolean;
    items: OrderLine[];
}

export interface OrderContactAddress {
    first_name: string | null;
    last_name: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country_code: string | null;
    phone: string | null;
}

export interface TrackedOrderData {
    id: number;
    email: string;
    created_at: string;
    currency_code: string;
    prices_include_tax: boolean;
    subtotal: string;
    discount_total: string;
    shipping_total: string;
    tax_total: string;
    total: string;
    groups: OrderShipmentGroup[];
    shipping_address: OrderContactAddress | null;
    billing_address: OrderContactAddress | null;
}

export type ReviewData = Pick<Review, 'id' | 'rating' | 'title' | 'content' | 'created_at'> & {
    user: Pick<User, 'name'> | null;
};

export type BrandListItem = Required<Pick<Brand, 'id' | 'name' | 'url_handle' | 'image'>>;

export type CategoryIndexChild = Pick<Category, 'id' | 'url_handle' | 'name'>;

export interface CategoryIndexItem extends CategoryIndexChild {
    product_count: number;
    children: CategoryIndexChild[];
}

// ---------------------------------------------------------------------------
// Storefront render types — produced by StorefrontHomepageDataQuery. Product
// data arrives as render-ready cards; text stays translatable for the client.
// ---------------------------------------------------------------------------

export type FeaturedProductsRenderSettings = ProductSourceRenderSettings;

export type ProductCarouselRenderSettings = ProductSourceRenderSettings;

export interface ProductTabRender extends ProductSourceRenderSettings {
    label: TranslatableField;
}

export interface ProductTabsRenderSettings {
    tabs: ProductTabRender[];
}

export interface ProductColumnRender extends ProductSourceRenderSettings {
    heading: TranslatableField;
}

export interface ProductColumnsRenderSettings {
    columns: ProductColumnRender[];
}

interface RenderedSection<T extends StorefrontSectionType, S> {
    id: number;
    type: T;
    title: TranslatableField;
    settings: S;
}

export type HeroSliderSectionData = RenderedSection<'hero_slider', HeroSliderSettings>;
export type InfoStripSectionData = RenderedSection<'info_strip', InfoStripSettings>;
export type CategoryGridSectionData = RenderedSection<'category_grid', CategoryGridSettings>;
export type FeaturedProductsSectionData = RenderedSection<'featured_products', FeaturedProductsRenderSettings>;
export type ProductTabsSectionData = RenderedSection<'product_tabs', ProductTabsRenderSettings>;
export type PromoBannersSectionData = RenderedSection<'promo_banners', PromoBannersSettings>;
export type ProductCarouselSectionData = RenderedSection<'product_carousel', ProductCarouselRenderSettings>;
export type ProductColumnsSectionData = RenderedSection<'product_columns', ProductColumnsRenderSettings>;
export type BrandStripSectionData = RenderedSection<'brand_strip', BrandStripSettings>;
export type TestimonialsSectionData = RenderedSection<'testimonials', TestimonialsSettings>;

export type HomepageSection =
    | HeroSliderSectionData
    | InfoStripSectionData
    | CategoryGridSectionData
    | FeaturedProductsSectionData
    | ProductTabsSectionData
    | PromoBannersSectionData
    | ProductCarouselSectionData
    | ProductColumnsSectionData
    | BrandStripSectionData
    | TestimonialsSectionData;

export interface MenuItemData {
    id: number;
    label: TranslatableField;
    url: string;
    target: string;
    is_mega_menu: boolean;
    featured_image: MediaItem | null;
    children?: MenuItemData[];
}

export interface Announcement {
    id: number;
    content: TranslatableField;
    url: string | null;
    is_active: boolean;
    sort_order: number;
    starts_at: string | null;
    ends_at: string | null;
}

export interface StorefrontAnnouncement {
    id: number;
    content: TranslatableField;
    url: string | null;
}

export interface StorefrontMenuFeatured {
    title: string;
    url: string | null;
    image: string | null;
}

export interface StorefrontMenuItem {
    label: string;
    url: string;
    target?: string;
    is_mega_menu?: boolean;
    featured?: StorefrontMenuFeatured | null;
    children?: StorefrontMenuItem[];
}

export interface StorefrontHeaderSettings {
    sticky: boolean;
}

export interface StorefrontFooterSettings {
    menu: StorefrontMenuItem[];
    description: string | null;
    showSocialLinks: boolean;
    showPaymentBadges: boolean;
    paymentMethods: PaymentMethodName[];
    showCopyright: boolean;
    copyrightText: string | null;
    showPoweredBy: boolean;
    socialLinks: Partial<Record<'facebook' | 'instagram' | 'x' | 'youtube', string>>;
}

export interface StorefrontLayout {
    store_email: string | null;
    store_phone: string | null;
    customCss: string;
    customJs: string;
    announcements: StorefrontAnnouncement[];
    trendingSearches: string[];
    header: StorefrontHeaderSettings;
    headerMenu: StorefrontMenuItem[];
    browseCategories: StorefrontMenuItem[];
    footer: StorefrontFooterSettings;
}

export interface SearchSuggestion {
    id: number;
    url_handle: string;
    title: TranslatableField;
    category: TranslatableField | null;
    price: string | null;
    compare_at_price: string | null;
    compare_at_price_range: [string, string] | null;
    price_range: [string, string] | null;
    featured_media: MediaItem | null;
    in_stock: boolean | null;
}

export interface ShopFacet {
    id: number;
    name: TranslatableField;
    url_handle: string;
    count: number;
}

export interface ShopPriceBucket {
    min: string | null;
    max: string | null;
}

export interface ShopFacets {
    categories: ShopFacet[] | null;
    brands: ShopFacet[] | null;
    price_buckets: ShopPriceBucket[];
    rating_buckets: number[];
}

export interface ShopFilters {
    query: string | null;
    categories: number[];
    brands: number[];
    min_price: string | null;
    max_price: string | null;
    in_stock: boolean | null;
    on_sale: boolean | null;
    rating: number | null;
    sort: string;
    per_page: number;
}

export interface ShopContext {
    type: 'category' | 'brand';
    name: TranslatableField;
    description: TranslatableField;
}
