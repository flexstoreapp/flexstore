<?php

declare(strict_types=1);

namespace App\Enums;

enum ShippingRateType: string
{
    case Flat = 'flat';
    case Free = 'free';
}
