<?php

declare(strict_types=1);

namespace App\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
