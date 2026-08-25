<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderAddressType: string
{
    case Shipping = 'shipping';
    case Billing = 'billing';
}
