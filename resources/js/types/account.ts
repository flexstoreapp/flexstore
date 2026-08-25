import type { TranslatableField } from './index';
import type { MediaItem } from './media';
import type { OrderItemDownload, FulfillmentStatus, PaymentStatus } from './order';

export interface AccountOrderItemPreview {
    id: number;
    product_id: number | null;
    product_title: TranslatableField;
    variant_title: string | null;
    quantity: number;
    total_price: string;
    media: MediaItem | null;
    product: { id: number; url_handle: string; is_active: boolean } | null;
}

interface AccountOrderBase {
    id: number;
    created_at: string;
    fulfillment_status: FulfillmentStatus;
    payment_status: PaymentStatus;
    canceled_at: string | null;
    total: string;
    currency_code: string;
}

export interface AccountOrderSummary extends AccountOrderBase {
    items_sum_quantity: number | null;
}

export interface AccountOrder extends AccountOrderBase {
    items_count: number;
    items: AccountOrderItemPreview[];
}

export interface AccountDashboardStats {
    total: number;
    unfulfilled: number;
    fulfilled: number;
    downloads: number;
}

export interface AccountDownload extends OrderItemDownload {
    order: { id: number; created_at: string } | null;
}
