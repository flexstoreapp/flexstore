<?php

declare(strict_types=1);

namespace App\Enums;

enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case InProgress = 'in_progress';
    case Fulfilled = 'fulfilled';
    case OnHold = 'on_hold';
}
