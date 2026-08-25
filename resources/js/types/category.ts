import type { TranslatableField } from '.';

export interface Category {
    id: number;
    name: TranslatableField;
    url_handle: string;
    description: TranslatableField | null;
    seo_title: TranslatableField | null;
    seo_description: TranslatableField | null;
    is_active: boolean;
    parent_id: number | null;
    parent?: Category | null;
    children?: Category[];
    ancestors?: Category[];
    descendants?: Category[];
    created_at: string;
    updated_at: string;
}
