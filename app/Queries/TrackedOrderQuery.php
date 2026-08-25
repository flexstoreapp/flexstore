<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Media;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class TrackedOrderQuery
{
    /**
     * @return array<string, mixed>
     */
    public function execute(int $orderId): array
    {
        $order = $this->fetchOrder($orderId);

        return [
            'id' => $order->id,
            'email' => $order->customer_email,
            'created_at' => $order->created_at->toIso8601String(),
            'currency_code' => $order->currency_code,
            'prices_include_tax' => $order->prices_include_tax,
            'subtotal' => $order->subtotal,
            'discount_total' => $order->discount_total,
            'shipping_total' => $order->shipping_total,
            'tax_total' => $order->tax_total,
            'total' => $order->total,
            'groups' => $this->groups($order),
            'shipping_address' => $this->address($order->shippingAddress),
            'billing_address' => $this->address($order->billingAddress),
        ];
    }

    private function fetchOrder(int $orderId): Order
    {
        return Order::query()
            ->select([
                'id', 'customer_email', 'created_at', 'currency_code', 'prices_include_tax',
                'subtotal', 'discount_total', 'shipping_total', 'tax_total', 'total',
                'shipping_carrier_name',
            ])
            ->with([
                'items:id,order_id,product_id,media_id,product_title,variant_title,quantity,unit_price,requires_shipping',
                'items.media:' . Media::displaySelect(),
                'items.product:id,url_handle,is_active',
                'shipments' => fn (Relation $query): Relation => $query
                    ->select(['id', 'order_id', 'shipping_carrier_id', 'tracking_number', 'tracking_url', 'delivery_status'])
                    ->oldest('id'),
                'shipments.carrier:id,name',
                'shipments.items:id,order_shipment_id,order_item_id,quantity',
                'shippingAddress:' . implode(',', $this->addressColumns()),
                'billingAddress:' . implode(',', $this->addressColumns()),
            ])
            ->findOrFail($orderId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groups(Order $order): array
    {
        $items = $order->items->keyBy('id');
        $groups = [];
        $shippedByItem = [];

        foreach ($order->shipments as $shipment) {
            $lines = [];

            foreach ($shipment->items as $shipmentItem) {
                $item = $items->get($shipmentItem->order_item_id);

                if (! $item instanceof OrderItem) {
                    continue;
                }

                $shippedByItem[$item->id] = ($shippedByItem[$item->id] ?? 0) + $shipmentItem->quantity;
                $lines[] = $this->line($item, $shipmentItem->quantity);
            }

            if ($lines !== []) {
                $groups[] = $this->shipmentGroup($shipment, $lines, $order->shipping_carrier_label);
            }
        }

        $pendingLines = [];
        $digitalLines = [];

        foreach ($order->items as $item) {
            if (! $item->requires_shipping) {
                $digitalLines[] = $this->line($item, $item->quantity);

                continue;
            }

            $remaining = $item->quantity - ($shippedByItem[$item->id] ?? 0);

            if ($remaining > 0) {
                $pendingLines[] = $this->line($item, $remaining);
            }
        }

        if ($pendingLines !== []) {
            $groups[] = [
                'key' => 'pending',
                'carrier_name' => null,
                'tracking_number' => null,
                'tracking_url' => null,
                'delivery_status' => null,
                'shipped' => false,
                'digital' => false,
                'items' => $pendingLines,
            ];
        }

        if ($digitalLines !== []) {
            $groups[] = [
                'key' => 'digital',
                'carrier_name' => null,
                'tracking_number' => null,
                'tracking_url' => null,
                'delivery_status' => null,
                'shipped' => false,
                'digital' => true,
                'items' => $digitalLines,
            ];
        }

        return $groups;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function shipmentGroup(OrderShipment $shipment, array $lines, string $carrierLabel): array
    {
        return [
            'key' => 'shipment-' . $shipment->id,
            'carrier_name' => $carrierLabel !== '' ? $carrierLabel : ($shipment->carrier->name ?? ''),
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $shipment->tracking_url,
            'shipped' => true,
            'digital' => false,
            'items' => $lines,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function line(OrderItem $item, int $quantity): array
    {
        return [
            'product_title' => $item->getTranslations('product_title'),
            'variant_title' => $item->variant_title,
            'url_handle' => $item->product?->is_active ? $item->product->url_handle : null,
            'featured_media' => $item->media,
            'quantity' => $quantity,
            'total_price' => (string) BigDecimal::of($item->unit_price)->multipliedBy($quantity),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function address(mixed $address): ?array
    {
        if ($address === null) {
            return null;
        }

        return [
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'state' => $address->state_name ?? $address->state,
            'postal_code' => $address->postal_code,
            'country_code' => $address->country_code,
            'phone' => $address->phone,
        ];
    }

    /**
     * @return list<string>
     */
    private function addressColumns(): array
    {
        return [
            'id', 'order_id',
            'first_name', 'last_name',
            'address_line_1', 'address_line_2',
            'city', 'state', 'postal_code', 'country_code', 'phone',
        ];
    }
}
