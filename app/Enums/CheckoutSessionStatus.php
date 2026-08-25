<?php

declare(strict_types=1);

namespace App\Enums;

enum CheckoutSessionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Canceled = 'canceled';
}
