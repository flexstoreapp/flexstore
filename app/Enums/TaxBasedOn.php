<?php

declare(strict_types=1);

namespace App\Enums;

enum TaxBasedOn: string
{
    case Shipping = 'shipping';
    case Billing = 'billing';
    case Store = 'store';
}
