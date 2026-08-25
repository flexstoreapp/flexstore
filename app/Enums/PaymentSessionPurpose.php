<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentSessionPurpose: string
{
    case Checkout = 'checkout';
}
