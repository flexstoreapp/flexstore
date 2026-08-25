import type { WeightUnit, TranslatableField, Brand, Category, User } from '.';
import type { MediaItem } from './media';

export interface ProductOptionValue {
    id: string;
    value: TranslatableField;
    product_option_id: string;
}

export interface ProductOption {
    id: string;
    product_id: number;
    name: TranslatableField;
    values: ProductOptionValue[];
}

export interface ProductVariantOption {
    option_id: string;
    value_id: string;
    name?: TranslatableField;
    value?: TranslatableField;
}

export interface ProductVariant {
    id: string;
    product_id: number;
    product?: Product | null;
    title: TranslatableField;
    options?: ProductVariantOption[];
    price: string;
    compare_at_price: string | null;
    cost_per_item: string | null;
    sku: string | null;
    barcode: string | null;
    track_stock: boolean;
    stock: number | null;
    low_stock_threshold?: number | null;
    in_stock: boolean;
    is_low_stock?: boolean;
    weight: string | null;
    weight_unit: string | null;
    length: string | null;
    width: string | null;
    height: string | null;
    dimension_unit: string | null;
    media?: MediaItem | null;
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

export type ProductType = 'physical' | 'digital';

export interface ProductDownload {
    id: string;
    variant_id: string | null;
    name: string;
    media_id: number;
    original_filename: string | null;
    file_size: number | null;
    mime_type: string | null;
    sort_order: number;
}

export interface Product {
    id: number;
    type: ProductType;
    downloads?: ProductDownload[];
    url_handle: string;
    title: TranslatableField;
    media_gallery?: MediaItem[];
    featured_media?: MediaItem | null;
    description: TranslatableField | null;
    category_id: number | null;
    category?: Category | null;
    brand_id: number | null;
    brand?: Brand | null;
    tax_category: string | null;
    price: string | null;
    price_range?: [string, string] | null;
    compare_at_price: string | null;
    compare_at_price_range?: [string, string] | null;
    cost_per_item: string | null;
    sku: string | null;
    barcode: string | null;
    track_stock: boolean;
    stock: number | null;
    low_stock_threshold?: number | null;
    total_stock?: number | null;
    in_stock: boolean;
    is_low_stock?: boolean;
    has_variants?: boolean;
    weight: string | null;
    weight_unit: WeightUnit | null;
    length: string | null;
    width: string | null;
    height: string | null;
    dimension_unit: string | null;
    is_tax_exempt: boolean;
    is_active: boolean;
    seo_title: TranslatableField;
    seo_description: TranslatableField | null;
    created_at: string;
    updated_at: string;
    options?: ProductOption[];
    variants?: ProductVariant[];
    rating?: number | null;
    review_count?: number | null;
    rating_distribution?: RatingDistribution;
}

export interface RatingDistribution {
    1: number;
    2: number;
    3: number;
    4: number;
    5: number;
}

export type VariantTabs = 'pricing' | 'inventory' | 'shipping' | 'media';

export type ReviewStatus = 'pending' | 'approved' | 'rejected';

export interface Review {
    id: number;
    product_id: number;
    user_id: number;
    rating: number;
    title: string | null;
    content: string;
    status: ReviewStatus;
    created_at: string;
    updated_at: string;
    product?: Product;
    user?: User;
}

export interface UploadProgress {
    fileId: string;
    progress: number;
    completed: boolean;
    error?: string;
}
