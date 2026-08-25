<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderTaxDetailItemType: string
{
    case Product = 'product';
    case Shipping = 'shipping';
}
