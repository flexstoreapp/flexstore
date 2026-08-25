import type { TranslatableField } from '.';
import type { Brand } from './brand';
import type { Category } from './category';
import type { MediaItem } from './media';

export type LinkType = 'brand' | 'category' | 'custom' | 'page';

export type MenuPage =
    | 'home'
    | 'products'
    | 'categories'
    | 'brands'
    | 'wishlist'
    | 'order_tracking'
    | 'refund_policy'
    | 'privacy_policy'
    | 'terms_of_service';

export interface MenuItem {
    id: number;
    location: 'header' | 'footer';
    label: TranslatableField;
    link_type: LinkType;
    brand_id: number | null;
    category_id: number | null;
    url: string | null;
    page: MenuPage | null;
    target: '_self' | '_blank';
    parent_id: number | null;
    sort_order: number;
    is_mega_menu: boolean;
    featured_image?: MediaItem | null;
    featured_title?: TranslatableField | null;
    featured_url?: string | null;
    is_active: boolean;
    brand?: Brand | null;
    category?: Category | null;
    parent?: MenuItem | null;
    children?: MenuItem[];
    created_at: string;
    updated_at: string;
}
