import { type SelectableBrand } from '@/components/admin/brand-picker';
import { type SelectableItem as CategorySelectableItem } from '@/components/admin/category-picker';
import { type SelectableItem as ProductSelectableItem } from '@/components/admin/product-picker';

import type { Region, TranslatableField } from '.';

export type ShippingCarrierDriver = 'manual' | 'shippo' | 'shiprocket';

export interface ShippingCarrier {
    id: number;
    name: TranslatableField;
    driver: ShippingCarrierDriver;
    is_active: boolean;
    collects_cod?: boolean;
    created_at: string;
    updated_at: string;
    rates?: ShippingRate[];
}

export type ShippingRateType = 'flat' | 'free' | 'live';

export interface ShippingRate {
    id: number;
    region_id: number;
    region?: Region;
    shipping_carrier_id: number;
    carrier?: ShippingCarrier;
    name: TranslatableField;
    type: ShippingRateType;
    rate: string | null;
    delivery_time: TranslatableField | null;
    min_order_value: string | null;
    max_order_value: string | null;
    min_weight: string | null;
    min_weight_unit: string | null;
    max_weight: string | null;
    max_weight_unit: string | null;
    excluded_products: string[] | ProductSelectableItem[];
    excluded_categories: string[] | CategorySelectableItem[];
    excluded_brands: string[] | SelectableBrand[];
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface ShippingOption {
    id: number | string;
    rate_id?: number;
    quote_reference?: string;
    service_code?: string;
    name: TranslatableField;
    carrier_name: TranslatableField;
    provider?: string | null;
    type: string;
    rate: string;
    delivery_time: TranslatableField | null;
}
