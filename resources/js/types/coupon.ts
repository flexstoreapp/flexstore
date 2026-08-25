export type CouponType = 'flat' | 'percentage';

export interface Coupon {
    id: number;
    code: string;
    type: CouponType;
    value: string;
    min_order_value: string | null;
    maximum_discount: string | null;
    usage_limit: number | null;
    usage_limit_per_customer: number | null;
    used_count: number;
    is_active: boolean;
    starts_at: string | null;
    expires_at: string | null;
    created_at: string;
    updated_at: string;
}
