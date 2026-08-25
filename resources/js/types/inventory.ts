import type { Product, ProductVariant, User } from '.';

export type StockMovementReason =
    | 'manual'
    | 'received'
    | 'damaged'
    | 'lost'
    | 'return'
    | 'inventory_count'
    | 'transfer'
    | 'sale'
    | 'refund'
    | 'cancellation'
    | 'other';

export interface StockMovement {
    id: number;
    product_id: number;
    product_variant_id: string | null;
    user_id: number | null;
    quantity: number;
    quantity_before: number;
    quantity_after: number;
    reason: StockMovementReason;
    reference_type: string | null;
    reference_id: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
    product?: Product;
    product_variant?: ProductVariant;
    user?: User | null;
}
