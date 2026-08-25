import type { TranslatableField } from '.';
import type { MediaItem } from './media';

export interface Brand {
    id: number;
    name: TranslatableField;
    url_handle: string;
    description: TranslatableField | null;
    seo_title: TranslatableField | null;
    seo_description: TranslatableField | null;
    image?: MediaItem | null;
    is_active: boolean;
    products_count?: number;
    created_at: string;
    updated_at: string;
}
