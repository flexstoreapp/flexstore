import type { ShippingRate, TranslatableField } from './';

export interface Region {
    id: number;
    name: TranslatableField;
    countries: string[];
    states: string[];
    postal_codes: string[];
    is_active: boolean;
    created_at: string;
    updated_at: string;
    shipping_rates?: ShippingRate[];
}
