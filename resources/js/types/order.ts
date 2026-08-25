export interface OrderItemDownload {
    id: string;
    order_id: number;
    order_item_id: number;
    token: string;
    name: string;
    original_filename: string;
    file_size: number;
    mime_type: string | null;
    download_count: number;
    last_downloaded_at: string | null;
    is_available: boolean;
    created_at: string;
}

import type { TranslatableField } from '.';
import type { User } from './auth';
import type { Coupon } from './coupon';
import type { MediaItem } from './media';
import type { PaymentGateway } from './payment';
import type { ProductVariant, Product } from './product';
import type { ShippingCarrier, ShippingRate } from './shipping';
import type { OrderTaxDetail } from './tax';

export type OrderAddressType = 'billing' | 'shipping';
export type OrderActivityType =
    | 'delivery_updated'
    | 'email_resent'
    | 'fulfillment_canceled'
    | 'fulfillment_status_changed'
    | 'items_fulfilled'
    | 'note_added'
    | 'order_canceled'
    | 'order_edited'
    | 'order_placed'
    | 'payment_received'
    | 'payment_request_sent'
    | 'payment_status_changed'
    | 'payment_voided'
    | 'refund_completed'
    | 'refund_failed'
    | 'refund_pending'
    | 'shipment_void_failed'
    | 'tracking_updated';

export type PaymentStatus =
    'unpaid' | 'partially_paid' | 'paid' | 'refunded' | 'partially_refunded' | 'failed' | 'canceled';

export type FulfillmentStatus = 'unfulfilled' | 'in_progress' | 'fulfilled' | 'on_hold';
export type CancellationReason = 'customer_request' | 'fraudulent' | 'inventory' | 'other';

export type RefundStatus = 'pending' | 'completed' | 'failed';
export type RefundItemType = 'product' | 'tax' | 'discount' | 'shipping' | 'restocking_fee' | 'adjustment';
export type TransactionType = 'sale' | 'void' | 'refund';
export type TransactionStatus = 'pending' | 'success' | 'failed';

export interface OrderAddress {
    id: number;
    order_id: number;
    type: OrderAddressType;
    first_name: string;
    last_name: string;
    address_line_1: string;
    address_line_2: string | null;
    city: string;
    state: string;
    state_name: string | null;
    postal_code: string;
    country_code: string;
    phone: string | null;
    created_at: string;
    updated_at: string;
}

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number | null;
    product_variant_id: string | null;
    product_title: TranslatableField;
    variant_title: string | null;
    variant_options?: Record<string, string> | null;
    product_sku: string | null;
    quantity: number;
    unit_price: string;
    total_price: string;
    tax_amount: string;
    requires_shipping: boolean;
    weight?: string | null;
    weight_unit?: string | null;
    media?: MediaItem | null;
    created_at: string;
    updated_at: string;
    product?: Product;
    product_variant?: ProductVariant;
    tax_details?: OrderTaxDetail[];
}

export interface OrderActivity {
    id: number;
    order_id: number;
    user_id?: number;
    type: OrderActivityType;
    comment?: string | null;
    metadata?: Record<string, unknown>;
    user?: User;
    created_at: string;
}

export interface OrderRefundItem {
    id: number;
    order_refund_id: number;
    type: RefundItemType;
    order_item_id: number | null;
    quantity: number | null;
    restock: boolean;
    amount: string;
    order_item?: OrderItem;
    created_at: string;
    updated_at: string;
}

export interface OrderRefund {
    id: number;
    order_id: number;
    status: RefundStatus;
    amount: string;
    is_manual_total: boolean;
    reason?: string;
    items: OrderRefundItem[];
    created_at: string;
    updated_at: string;
}

export interface OrderTransaction {
    id: number;
    order_id: number;
    order_refund_id: number | null;
    type: TransactionType;
    status: TransactionStatus;
    amount: string;
    currency_code: string;
    gateway_reference: string | null;
    payment_method: string | null;
    payment_method_details: Record<string, unknown> | null;
    is_manual_entry: boolean;
    related_transaction_id: number | null;
    failure_reason: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
}

export interface OrderShipmentItem {
    id: number;
    order_shipment_id: number;
    order_item_id: number;
    quantity: number;
    order_item?: OrderItem;
    created_at: string;
    updated_at: string;
}

export interface OrderShipment {
    id: number;
    order_id: number;
    user_id: number | null;
    tracking_number: string | null;
    tracking_url: string | null;
    shipped_at: string | null;
    items: OrderShipmentItem[];
    user?: User;
    created_at: string;
    updated_at: string;
}

export interface Order {
    item_downloads?: OrderItemDownload[];
    id: number;
    customer_id: number | null;
    customer?: User | null;
    payment_status: PaymentStatus;
    fulfillment_status: FulfillmentStatus;
    customer_email: string;
    prices_include_tax: boolean;
    shipping_is_taxable: boolean;
    subtotal: string;
    discount_total: string;
    shipping_total: string;
    tax_total: string;
    total: string;
    currency_code: string;
    exchange_rate: string;
    shipping_carrier_id: number | null;
    shipping_carrier_name: TranslatableField | null;
    shipping_carrier?: ShippingCarrier;
    shipping_rate_id: number | null;
    shipping_rate_name: TranslatableField | null;
    region_id: number | null;
    region_name: TranslatableField | null;
    shipping_rate?: ShippingRate;
    payment_gateway_id: number | null;
    payment_gateway_name: TranslatableField | null;
    payment_gateway?: PaymentGateway;
    coupon_id: number | null;
    coupon?: Coupon | null;
    coupon_code: string | null;
    notes: string | null;
    canceled_at: string | null;
    cancellation_reason: CancellationReason | null;
    cancellation_note: string | null;
    is_canceled?: boolean;
    is_voidable?: boolean;
    is_refundable?: boolean;
    is_cancellable?: boolean;
    paid_total: string;
    refund_total: string;
    net_paid_total: string;
    balance_due_total: string;
    credit_due_total: string;
    has_outstanding_balance?: boolean;
    has_credit_owed?: boolean;
    can_collect_cod?: boolean;
    can_request_payment?: boolean;
    can_record_payment?: boolean;
    can_refund_credit?: boolean;
    items_sum_quantity?: number;
    items?: OrderItem[];
    addresses?: OrderAddress[] | null;
    billing_address?: OrderAddress | null;
    shipping_address?: OrderAddress | null;
    activities?: OrderActivity[];
    tax_details?: OrderTaxDetail[];
    shipments?: OrderShipment[];
    refunds?: OrderRefund[];
    transactions?: OrderTransaction[];
    created_at: string;
    updated_at: string;
}
