import type { Region, TranslatableField } from '.';

export interface TaxCategoryOption {
    value: string;
    label: string;
}

export interface TaxRate {
    id: number;
    name: TranslatableField;
    type: string;
    region_id: number;
    region?: Region;
    tax_category: string | null;
    rate: string;
    min_order_value: string | null;
    max_order_value: string | null;
    is_compound: boolean;
    is_active: boolean;
    priority: number;
    created_at: string;
    updated_at: string;
}

export interface TaxDetail {
    tax_name: TranslatableField;
    tax_rate: string;
    taxable_amount: string;
    tax_amount: string;
}

export type OrderTaxDetailItemType = 'product' | 'shipping';

export interface OrderTaxDetail {
    id: number;
    order_id: number;
    order_item_id: number | null;
    tax_rate_id: number;
    item_type: OrderTaxDetailItemType;
    tax_name: TranslatableField;
    tax_rate: string;
    taxable_amount: string;
    tax_amount: string;
    proportion: string | null;
    is_compound: boolean;
    created_at: string;
    updated_at: string;
}
