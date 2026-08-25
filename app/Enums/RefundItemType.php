<?php

declare(strict_types=1);

namespace App\Enums;

enum RefundItemType: string
{
    case Product = 'product';
    case Tax = 'tax';
    case Discount = 'discount';
    case Shipping = 'shipping';
    case RestockingFee = 'restocking_fee';
    case Adjustment = 'adjustment';
}
