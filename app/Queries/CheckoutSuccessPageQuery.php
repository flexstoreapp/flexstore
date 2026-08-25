<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;

final readonly class CheckoutSuccessPageQuery
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Order $order, bool $isBuyer): array
    {
        $order->loadMissing('items:id,order_id,product_id,product_title,variant_title,unit_price,quantity');

        return [
            'emailConfirmation' => (bool) Setting::getValue('notification_customer_order_confirmed'),
            'isBuyer' => $isBuyer,
            'order' => [
                'id' => $order->id,
                'trackUrl' => $isBuyer ? URL::signedRoute('orders.track.show', ['order' => $order->id]) : null,
                'currency_code' => $order->currency_code,
                'total' => $order->total,
                'tax_total' => $order->tax_total,
                'shipping_total' => $order->shipping_total,
                'coupon_code' => $order->coupon_code,
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'product_id' => $item->product_id,
                    'product_title' => $item->getTranslations('product_title'),
                    'variant_title' => $item->variant_title,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                ]),
            ],
        ];
    }
}
