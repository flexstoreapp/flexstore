import type { User } from '.';

export interface CustomerAddress {
    id: number;
    user_id: number;
    first_name: string;
    last_name: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string;
    state_name: string | null;
    postal_code: string;
    country_code: string;
    phone: string;
    is_default: boolean;
    created_at: string;
    updated_at: string;
}

export interface Customer extends User {
    lifetime_value: string;
    addresses?: CustomerAddress[];
    order_count?: number;
    last_fulfilled_order_date?: string | null;
}
