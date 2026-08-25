<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponType: string
{
    case Flat = 'flat';
    case Percentage = 'percentage';
}
