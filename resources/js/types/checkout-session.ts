export type CheckoutSessionStatus = 'pending' | 'completed' | 'canceled';
export interface CheckoutSessionAddress {
    first_name?: string | null;
    last_name?: string | null;
    address_line_1?: string | null;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    state_name?: string | null;
    postal_code?: string | null;
    country_code?: string | null;
    phone?: string | null;
}
