<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\Setting;

final readonly class EmailSchemaBuilder
{
    /**
     * @return array<string, mixed>
     */
    public static function forOrder(Order $order, string $orderStatus, ?string $viewUrl = null): array
    {
        $storeName = Setting::getValue('store_name', config('app.name'));
        $storeUrl = route('home');

        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                '@type' => 'OrderItem',
                'orderQuantity' => $item->quantity,
                'orderedItem' => [
                    '@type' => 'Product',
                    'name' => self::translatableString($item->product_title),
                    'sku' => $item->product_sku,
                ],
                'orderItemStatus' => $orderStatus,
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Order',
            'merchant' => [
                '@type' => 'Organization',
                'name' => $storeName,
                'url' => $storeUrl,
            ],
            'orderNumber' => (string) $order->id,
            'orderStatus' => $orderStatus,
            'priceCurrency' => $order->currency_code,
            'price' => $order->total,
            'acceptedOffer' => $items,
            'orderDate' => $order->created_at->toAtomString(),
            'url' => $viewUrl ?? $storeUrl,
        ];

        if ($order->customer_email) {
            $schema['customer'] = [
                '@type' => 'Person',
                'email' => $order->customer_email,
                'name' => mb_trim(($order->shippingAddress->first_name ?? '') . ' ' . ($order->shippingAddress->last_name ?? '')) ?: null,
            ];
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forShipment(Order $order, OrderShipment $shipment): array
    {
        $orderSchema = self::forOrder($order, 'https://schema.org/OrderInTransit');

        $delivery = [
            '@type' => 'ParcelDelivery',
            'partOfOrder' => $orderSchema,
        ];

        if ($shipment->tracking_number) {
            $delivery['trackingNumber'] = $shipment->tracking_number;
        }
        if ($shipment->tracking_url) {
            $delivery['trackingUrl'] = $shipment->tracking_url;
        }
        if ($order->shipping_carrier_name) {
            $delivery['carrier'] = [
                '@type' => 'Organization',
                'name' => $order->shipping_carrier_label,
            ];
        }
        if ($order->shippingAddress) {
            $delivery['deliveryAddress'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => mb_trim(($order->shippingAddress->address_line_1 ?? '') . ' ' . ($order->shippingAddress->address_line_2 ?? '')),
                'addressLocality' => $order->shippingAddress->city,
                'addressRegion' => $order->shippingAddress->state,
                'postalCode' => $order->shippingAddress->postal_code,
                'addressCountry' => $order->shippingAddress->country_code,
            ];
        }

        return [
            '@context' => 'https://schema.org',
            ...$delivery,
        ];
    }

    /**
     * @param  array<string, string>|string|null  $value
     */
    private static function translatableString(array|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }

        return $value[app()->getLocale()] ?? $value['en'] ?? (string) reset($value);
    }
}
