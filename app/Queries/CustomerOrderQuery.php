<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Media;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItemDownload;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class CustomerOrderQuery
{
    public function execute(int $orderId, User $user): Order
    {
        $order = Order::query()
            ->where('customer_id', $user->id)
            ->select([
                'id', 'customer_id', 'created_at',
                'fulfillment_status', 'payment_status',
                'canceled_at', 'cancellation_reason',
                'subtotal', 'discount_total', 'coupon_code',
                'shipping_total', 'tax_total', 'total',
                'currency_code', 'prices_include_tax',
                'paid_total', 'refund_total', 'net_paid_total',
                'balance_due_total', 'credit_due_total',
                'payment_gateway_name', 'shipping_carrier_name', 'shipping_rate_name', 'notes',
            ])
            ->with([
                'items:id,order_id,product_id,product_variant_id,media_id,product_title,variant_title,variant_options,quantity,unit_price,total_price,requires_shipping',
                'items.media:' . Media::displaySelect(),
                'items.product:id,url_handle,is_active',
                'billingAddress:id,order_id,type,first_name,last_name,address_line_1,address_line_2,city,state,postal_code,country_code,phone',
                'shippingAddress:id,order_id,type,first_name,last_name,address_line_1,address_line_2,city,state,postal_code,country_code,phone',
                'taxDetails:id,order_id,tax_name,tax_rate,taxable_amount,tax_amount',
                'refunds' => fn (Relation $query): Relation => $query
                    ->select(['id', 'order_id', 'status', 'amount', 'reason', 'created_at'])
                    ->latest(),
                'refunds.items:id,order_refund_id,type,order_item_id,quantity,amount',
                'refunds.items.orderItem:id,order_id,product_title,variant_title',
                'shipments' => fn (Relation $query): Relation => $query
                    ->select(['id', 'order_id', 'tracking_number', 'tracking_url', 'shipped_at', 'delivered_at', 'created_at'])
                    ->latest(),
                'shipments.items:id,order_shipment_id,order_item_id,quantity',
                'shipments.items.orderItem:id,order_id,product_title,variant_title',
                'itemDownloads' => fn (Relation $query): Relation => $query
                    ->select([
                        'id', 'order_id', 'order_item_id', 'token', 'name', 'original_filename',
                        'file_size', 'mime_type', 'download_count', 'last_downloaded_at', 'created_at',
                    ])
                    ->latest(),
            ])
            ->findOrFail($orderId);

        $order->itemDownloads->each(function (OrderItemDownload $download) use ($order): void {
            $download->setRelation('order', $order);
            $download->append(['is_available']);
        });

        $this->resolveAddressState($order->shippingAddress);
        $this->resolveAddressState($order->billingAddress);

        return $order;
    }

    private function resolveAddressState(?OrderAddress $address): void
    {
        $address?->setAttribute('state', $address->state_name ?? $address->state)
            ->makeHidden('state_name');
    }
}
