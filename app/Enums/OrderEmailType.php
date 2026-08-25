<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderEmailType: string
{
    case OrderConfirmed = 'order_confirmed';
    case OrderFulfilled = 'order_fulfilled';
    case TrackingUpdated = 'tracking_updated';
    case OrderRefunded = 'order_refunded';
    case OrderCanceled = 'order_canceled';
    case OrderUpdated = 'order_updated';
}
